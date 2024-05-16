<?php
/**
 * @package    Grav\Plugin\LikesRatings
 *
 * @copyright  Copyright (C) 2014 - 2017 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */
namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Common\Grav;
use Grav\Plugin\Database\Database;
use Grav\Plugin\LikesRatings\Likes;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CleanCommand
 *
 * @package Grav\Console\Cli
 */
class LsCommand extends ConsoleCommand
{
    /** @var array */
    protected $options = [];

    /** @var Likes */
    protected $likes;

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('ls')
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'The ID of the likes-ratings entry',
                ''
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit the list of page views',
                10
            )
            ->addOption(
                'sort',
                's',
                InputOption::VALUE_OPTIONAL,
                'Sort the list of likes ratings (desc / asc)',
                'desc'
            )
            ->addOption(
                'by',
                '',
                InputOption::VALUE_OPTIONAL,
                'Sort the list of likes ratings by (ups / downs)',
                'ups'
            )
            ->setDescription('List the page views count')
            ->setHelp('The <info>list</info> command displays the page views count')
        ;
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        include __DIR__ . '/../vendor/autoload.php';

        $grav = Grav::instance();
        $io = new SymfonyStyle($this->input, $this->output);

        // Initialize Plugins
        $grav->fireEvent('onPluginsInitialized');

        $id = $this->input->getArgument('id');
        $limit = $this->input->getOption('limit');
        $sort = $this->input->getOption('sort');
        $by = $this->input->getOption('by');

        $likes = $grav['likes'];

        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders(['ID', 'Ups 👍', 'Downs 👎', 'Score 🎯', 'Total 🟰']);
        $rows = [];

        if ($id) {
            $entry = $likes->get($id);
            $rows[] = [$id, $entry['ups'],$entry['downs'], $entry['ups'] - $entry['downs'], $entry['ups'] + $entry['downs']];
        } else {
            $total = $likes->getAll($limit, $by, $sort);
            foreach ($total as $view) {
                $rows[] = [$view['id'], $view['ups'], $view['downs'], $view['ups'] - $view['downs'], $view['ups'] + $view['downs']];
            }
        }

        $io->title('Likes Ratings List');
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
