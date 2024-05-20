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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CleanCommand
 *
 * @package Grav\Console\Cli
 */
class SetCommand extends ConsoleCommand
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
            ->setName('set')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'The ID of the likes-ratings entry'
            )
            ->addArgument(
                'count',
                InputArgument::REQUIRED,
                'The amount of "ups" or "downs" to set for the chosen ID'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Either "ups" or "downs" as the type wanted to be changed (default: "ups")',
                'ups'
            )
            ->setDescription('Set the likes ratings count for an ID entry')
            ->setHelp('The <info>set</info> command allow to manually set a likes ratings count for an id')
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
        $count = $this->input->getArgument('count');
        $type = $this->input->getOption('type');

        if (!preg_match('/^[-+]?[0-9]*\\.?[0-9]+$/', $count)) {
            $io->error('Count argument must be a proper number. Example, 10, +5, -1, 3.2');
            exit;
        }

        $likes = $grav['likes'];

        if (in_array(substr($count, 0, 1), ['+', '-'])) {
            $likes->add($id, $type, $count);
            $count = $likes->get($id, $type);
        } else {
            $likes->set($id, $type, $count);
        }

        $io->title('Set Likes Ratings Count');
        $io->text('Likes Ratings <green>'. $id . '</green> updated to <cyan>' . $count . ' ' . $type . '</cyan>');
        $io->newLine();
    }
}
