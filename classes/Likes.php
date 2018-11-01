<?php
namespace Grav\Plugin\LikesRatings;

use Grav\Common\Grav;
use Grav\Common\Config\Config;
use Grav\Plugin\Database\PDO;

class Likes
{
    /** @var PDO */
    protected $db;

    protected $config;
    protected $path = '/likes-ratings';
    protected $db_name = '/likes.db';
    protected $table_likes = 'likes';
    protected $table_ips = 'ips';

    public function __construct($config)
    {
        $this->config = new Config($config);

        $db_path = Grav::instance()['locator']->findResource('user://data', true) . $this->path;

        // Create dir if it doesn't exist
        if (!file_exists($db_path)) {
            mkdir($db_path);
        }

        $connect_string = 'sqlite:' . $db_path . $this->db_name;

        $this->db = Grav::instance()['database']->connect($connect_string);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->db->tableExists($this->table_likes)) {
            $this->createTables();
        }

    }

    public function add($id, $col = 'ups', $amount = 1)
    {
        $status = false;
        $message = "Thanks for your vote";

        if (!in_array($col, ['ups', 'downs'])) {
            $message = "Invalid vote type: $col";
        } elseif ($this->processIP($id)) {
            $statement = "INSERT INTO {$this->table_likes} (id, $col) VALUES ('$id', $amount) ON CONFLICT(id) DO UPDATE SET $col = $col + $amount";
            $this->db->insert($statement);
            $status = true;
        } else {
            $message = "This IP has already voted";
        }

        $count = $this->get($id, $col);
        return [$status, $message, $count];
    }

    public function get($id, $col = '*')
    {
        $statement = "SELECT $col FROM {$this->table_likes} WHERE id = '$id'";
        $results = $this->db->select($statement);

        if ($col == '*') {
            return $results;
        }
        return $results[$col] ?? 0;
    }

    public function processIP($id)
    {
        if ($this->config->get('unique_ip_check')) {
            $user_ip = Grav::instance()['uri']->ip();

            $statement = "INSERT INTO {$this->table_ips} (id, ip) VALUES ('$id', '$user_ip') ON CONFLICT DO NOTHING";
            $results = $this->db->insert($statement);

            return $results == 1 ? true : false;
        }
        return true;
    }

    public function createTables()
    {
        $commands = [
            "CREATE TABLE IF NOT EXISTS likes (id VARCHAR(255) PRIMARY KEY, ups INTEGER DEFAULT 0, downs INTEGER DEFAULT 0)",
            "CREATE TABLE IF NOT EXISTS ips (id VARCHAR(255), ip varchar(100), PRIMARY KEY (id, ip))"
        ];

        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->db->exec($command);
        }
    }



}