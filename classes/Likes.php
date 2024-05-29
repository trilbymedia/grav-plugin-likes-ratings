<?php
namespace Grav\Plugin\LikesRatings;

use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Config\Config;
use Grav\Common\Utils;
use Grav\Plugin\Database\PDO;

class Likes
{
    const UP = 'ups';
    const DOWN = 'downs';

    /** @var PDO */
    protected $db;

    protected $config;
    protected $path = 'user-data://likes-ratings';
    protected $db_name = 'likes.db';
    protected $table_likes = 'likes';
    protected $table_ips = 'ips';

    public function __construct($config)
    {
        $this->config = new Config($config);

        $db_path = Grav::instance()['locator']->findResource($this->path, true, true);

        // Create dir if it doesn't exist
        if (!file_exists($db_path)) {
            Folder::create($db_path);
        }

        $connect_string = 'sqlite:' . $db_path . '/' . $this->db_name;

        $this->db = Grav::instance()['database']->connect($connect_string);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->db->tableExists($this->table_likes)) {
            $this->createTables();
        }

    }

    public function add($id, $col = 'ups', $amount = 1)
    {
        $status = false;
        $error = null;

        if (!\in_array($col, ['ups', 'downs'])) {
            $error = "Invalid vote type: $col";
        } elseif (!$this->processIP($id)) {
            $error = 'This IP has already voted';
        } elseif (!$this->supportOnConflict()) {
            // Support SQLite < 3.24
            $query = "UPDATE {$this->table_likes} SET {$col} = {$col} + :amount WHERE id = :id";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
            $statement->execute();

            if ($statement->rowCount() === 0) {
                $query = "INSERT INTO {$this->table_likes} (id, {$col}) VALUES (:id, :amount)";

                $statement = $this->db->prepare($query);
                $statement->bindValue(':id', $id, PDO::PARAM_STR);
                $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
                $statement->execute();
            }

            $status = true;
        } else {
            // Support SQLite >= 3.24
            $query = "INSERT INTO {$this->table_likes} (id, {$col}) VALUES (:id, :amount) ON CONFLICT(id) DO UPDATE SET {$col} = {$col} + :amount";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
            $statement->execute();

            $status = true;
        }

        if (!defined('GRAV_CLI')) {
            $payload = $this->generateLikes($id, $error, true);
            return [$status, $error, $payload];
        } else {
            return [$status, $error];
        }

    }

    public function set($id, $col = 'ups', $amount = 1)
    {
        if (!\in_array($col, ['ups', 'downs'])) {
            return false;
        }

        if (!$this->supportOnConflict()) {
            // Support SQLite < 3.24
            $query = "UPDATE {$this->table_likes} SET {$col} = :amount WHERE id = :id";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
            $statement->execute();

            if ($statement->rowCount() === 0) {
                $query = "INSERT INTO {$this->table_likes} (id, {$col}) VALUES (:id, :amount)";

                $statement = $this->db->prepare($query);
                $statement->bindValue(':id', $id, PDO::PARAM_STR);
                $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
                $statement->execute();
            }
        } else {
            // Support SQLite >= 3.24
            $query = "INSERT INTO {$this->table_likes} (id, {$col}) VALUES (:id, :amount) ON CONFLICT(id) DO UPDATE SET {$col} = :amount";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':amount', $amount, PDO::PARAM_INT);
            $statement->execute();
        }

        return true;
    }

    public function get($id, $col = '*')
    {
        $query = "SELECT {$col} FROM {$this->table_likes} WHERE id = :id";

        $statement = $this->db->prepare($query);
        $statement->bindValue(':id', $id, PDO::PARAM_STR);
        $statement->execute();

        $results = $statement->fetch();

        if ($col === '*') {
            return $results;
        }

        return $results[$col] ?? 0;
    }

    public function getAll($limit = 0, $order = 'ups', $by = 'ASC')
    {
        $by = strtoupper($by) === 'ASC' ? 'ASC' : 'DESC';
        $offset = 0;

        $query = "SELECT * FROM {$this->table_likes} ORDER BY {$order} {$by} LIMIT :limit OFFSET :offset";
        $statement = $this->db->prepare($query);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function processIP($id)
    {
        if ($this->config->get('unique_ip_check')) {
            $user_ip = Grav::instance()['uri']->ip();

            $query = "SELECT id FROM {$this->table_ips} WHERE id = :id AND ip = :ip";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':ip', $user_ip, PDO::PARAM_STR);
            $statement->execute();

            $results = $statement->fetch();

            if ($results) {
                return false;
            }

            $query = "INSERT INTO {$this->table_ips} (id, ip) VALUES (:id, :ip)";

            $statement = $this->db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_STR);
            $statement->bindValue(':ip', $user_ip, PDO::PARAM_STR);
            $statement->execute();
        }

        return true;
    }

    /**
     * @param mixed|null $id
     * @param array $options
     * @return string
     */
    public function generateLikes($id, $error = null, $disabled = false)
    {
       if (null === $id) {
            return '';
        }

        // Convert objects to string
        $id = (string)$id;

        $twig = Grav::instance()['twig'];
        $likes = Grav::instance()['likes'];
        $options = $this->config->toArray();

        $options['readonly'] = $options['readonly'] || ($options['disable_after_vote'] && $disabled);

        $results = $likes->get($id);

        $callback = Uri::addNonce(Utils::url($options['callback']) . '.json','likes-ratings');

        return $twig->processTemplate($options['twig_template'], [
            'id'        => $id,
            'uri'       => $callback,
            'ups'       => $results['ups'] ?? 0,
            'downs'     => $results['downs'] ?? 0,
            'options'   => $options,
            'error'     => $error
        ]);
    }

    public function getId($id = null): ?string
    {
        return $id ?? Grav::instance()['page']->route();
    }

    public function createTables()
    {
        $commands = [
            "CREATE TABLE IF NOT EXISTS {$this->table_likes}  (id VARCHAR(255) PRIMARY KEY, ups INTEGER DEFAULT 0, downs INTEGER DEFAULT 0)",
            "CREATE TABLE IF NOT EXISTS {$this->table_ips} (id VARCHAR(255), ip varchar(100), PRIMARY KEY (id, ip))"
        ];

        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->db->exec($command);
        }
    }

    public function saveOptions($id, $options): void
    {
        $options_file = static::getOptionsFile($id);
        $options = array_map(function($value) {
            if (is_string($value) || is_numeric($value)) {
                switch (strtolower((string)$value)) {
                    case "true":
                    case "1":
                    case "1.0":
                        return true;
                    case "false":
                    case "0":
                    case "0.0":
                        return false;
                }
            }
            return $value;
        }, $options);
        $options_file->save($options);
        $this->mergeSavedOptions($id);
    }
    public function loadOptions($id): array
    {
        $options_file = $this->getOptionsFile($id);

        if ($options_file->exists()) {
            return $options_file->content();
        }

        return [];
    }

    public function mergeSavedOptions($id)
    {
        $saved_options = $this->loadOptions($id);
        if (!empty($saved_options)) {
            $this->config = new Config(array_merge($this->config->toArray(), $saved_options));
        }
    }

    protected function getOptionsFile($id): CompiledYamlFile
    {
        $path = Grav::instance()['locator']->findResource('user-data://likes-ratings', true, true);
        if (!file_exists($path)) {
            Folder::create($path);
        }
        $options_path = $path  . '/' . md5($id) . '.yaml';
        return CompiledYamlFile::instance($options_path);
    }

    protected function supportOnConflict()
    {
        static $bool;

        if ($bool === null) {
            $bool = version_compare($this->db->query('SELECT sqlite_version()')->fetch()[0], '3.24' , '>=');
        }

        return $bool;
    }
}
