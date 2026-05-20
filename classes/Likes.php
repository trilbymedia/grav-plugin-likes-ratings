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
    const ID_REGEX = '#^/?[a-zA-Z0-9][a-zA-Z0-9/._-]{0,254}$#';

    /** @var PDO */
    protected $db;

    protected $config;
    protected $path = 'user-data://likes-ratings';
    protected $db_name = 'likes.db';
    protected $table_likes = 'likes';
    protected $table_ips = 'ips';
    protected $table_rate_limits = 'rate_limits';
    protected $table_meta = 'meta';

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

        $this->createTables();
    }

    public function add($id, $col = 'ups', $amount = 1)
    {
        $status = false;
        $error = null;

        if (!\in_array($col, ['ups', 'downs'], true)) {
            $error = 'Invalid vote type';
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
        $order = in_array($order, ['ups', 'downs', 'id'], true) ? $order : 'ups';
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
            "CREATE TABLE IF NOT EXISTS {$this->table_ips} (id VARCHAR(255), ip varchar(100), PRIMARY KEY (id, ip))",
            "CREATE TABLE IF NOT EXISTS {$this->table_rate_limits} (ip VARCHAR(100), ts INTEGER)",
            "CREATE INDEX IF NOT EXISTS {$this->table_rate_limits}_ip_ts_idx ON {$this->table_rate_limits} (ip, ts)",
            "CREATE TABLE IF NOT EXISTS {$this->table_meta} (name VARCHAR(64) PRIMARY KEY, value TEXT)",
        ];

        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->db->exec($command);
        }
    }

    /**
     * Allowlist check for vote ids: route-like strings, 1-255 chars.
     */
    public static function isValidId($id): bool
    {
        return is_string($id) && (bool) preg_match(self::ID_REGEX, $id);
    }

    /**
     * Sweeps rows with invalid ids from the likes/ips tables, but only once per configured
     * interval. Safe to call on every vote — early-exits if it ran recently.
     *
     * @return int Number of likes rows removed (0 if no sweep ran).
     */
    public function maybeCleanup(): int
    {
        if (!$this->config->get('auto_cleanup_enabled', true)) {
            return 0;
        }

        $hours = max(1, (int) $this->config->get('auto_cleanup_interval_hours', 24));
        $now = time();
        $last = (int) $this->getMeta('last_cleanup_ts');

        if ($last && ($now - $last) < $hours * 3600) {
            return 0;
        }

        $invalid = [];
        $stmt = $this->db->query("SELECT id FROM {$this->table_likes}");
        foreach ($stmt as $row) {
            $id = $row['id'] ?? ($row[0] ?? null);
            if ($id !== null && !static::isValidId($id)) {
                $invalid[] = $id;
            }
        }

        $deleted = 0;
        if ($invalid) {
            $delLikes = $this->db->prepare("DELETE FROM {$this->table_likes} WHERE id = :id");
            $delIps = $this->db->prepare("DELETE FROM {$this->table_ips} WHERE id = :id");
            foreach ($invalid as $id) {
                $delLikes->bindValue(':id', $id, PDO::PARAM_STR);
                $delLikes->execute();
                $deleted += $delLikes->rowCount();
                $delIps->bindValue(':id', $id, PDO::PARAM_STR);
                $delIps->execute();
            }
        }

        $this->setMeta('last_cleanup_ts', (string) $now);

        return $deleted;
    }

    protected function getMeta(string $name): ?string
    {
        $stmt = $this->db->prepare("SELECT value FROM {$this->table_meta} WHERE name = :name");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    protected function setMeta(string $name, string $value): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table_meta} SET value = :value WHERE name = :name");
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $stmt = $this->db->prepare("INSERT INTO {$this->table_meta} (name, value) VALUES (:name, :value)");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':value', $value, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    /**
     * Returns true if the IP is under the per-minute vote limit, false if it should be blocked.
     * Records the vote attempt as a side effect when allowed.
     */
    public function checkRateLimit(string $ip): bool
    {
        if (!$this->config->get('rate_limit_enabled', true)) {
            return true;
        }

        $limit = (int) $this->config->get('rate_limit_per_minute', 10);
        if ($limit <= 0) {
            return true;
        }

        $now = time();
        $cutoff = $now - 60;

        $statement = $this->db->prepare("DELETE FROM {$this->table_rate_limits} WHERE ts < :cutoff");
        $statement->bindValue(':cutoff', $cutoff, PDO::PARAM_INT);
        $statement->execute();

        $statement = $this->db->prepare("SELECT COUNT(*) FROM {$this->table_rate_limits} WHERE ip = :ip AND ts >= :cutoff");
        $statement->bindValue(':ip', $ip, PDO::PARAM_STR);
        $statement->bindValue(':cutoff', $cutoff, PDO::PARAM_INT);
        $statement->execute();
        $count = (int) $statement->fetchColumn();

        if ($count >= $limit) {
            return false;
        }

        $statement = $this->db->prepare("INSERT INTO {$this->table_rate_limits} (ip, ts) VALUES (:ip, :ts)");
        $statement->bindValue(':ip', $ip, PDO::PARAM_STR);
        $statement->bindValue(':ts', $now, PDO::PARAM_INT);
        $statement->execute();

        return true;
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
