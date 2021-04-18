<?php


namespace Brother\CommonBundle\Command;


use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Cache\BrotherCacheProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class SingletonCommand extends BaseCommand {

    protected $discord = null;

    abstract protected function doExecute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): array;

    protected function configure() {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $cache = $this->getContainer()->get('brother_cache');
        $class = get_class($this);
        $io = new SymfonyStyle($input, $output);
        if ($last = $cache->getSemafor($class, BrotherCacheProvider::SEMAFOR_FINISHED)) {
            $io->writeln("Последний запуск: " . $last);
        }

        if ($cache->inProgress($class) && !$input->getOption('force')) {
            $output->writeln('Команда уже работает');
            return 0;
        }
        $cache->setSemafor($class, BrotherCacheProvider::SEMAFOR_IN_PROGRESS, 3600 * 4);
        $cache->removeSemafor($class, BrotherCacheProvider::SEMAFOR_STARTING);

        AppDebug::commandStatus($input, $io, $class, 'start', $input->getOptions(), [
            'discord' => $this->discord
        ]);
        $result = $this->doExecute($input, $output, $io);
        $io->writeln('');
        $r = AppDebug::commandStatus($input, $io, $class, 'end', $result, [
            'discord' => $this->discord
        ]);
        $s = json_encode(array_merge(['finish' => date("Y-m-d h:i:s"), 'time' => $r['time'], 'mem' => round(memory_get_peak_usage()/1048576) . 'M'], $result));
        $io->writeln($s);
        $cache->setSemafor($class, BrotherCacheProvider::SEMAFOR_FINISHED, 86400, $s);
        $cache->removeSemafor($class, BrotherCacheProvider::SEMAFOR_IN_PROGRESS);
        return 0;
    }

}
