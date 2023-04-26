<?php
/**
 * Created by PhpStorm.
 * User: cyx-sanjay
 * Date: 23/11/2016
 * Time: 11:12 AM
 */

namespace KolossusD\CyxAuthBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('cyx:auth:generate')
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates new users.')
            // configure an argument
            ->addArgument('action', InputArgument::REQUIRED, 'The page for frontend.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to transfer file...")
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        try {
            switch ($input->getArgument('action')) {
                case 'login-page':
                    if($fs->exists('app/Resources/views/Auth') === false){
                        $fs->mirror(__DIR__.'/../Resources/views/Auth', 'app/Resources/views/Auth');
                        $output->writeln('Auth folder transfer Successfully');
                    }else{
                        $output->writeln('Auth folder already exist!');
                    }
                    if($fs->exists('app/config/captcha.php') === false){
                        $fs->mirror(__DIR__.'/../source/captcha', 'app/config');
                        $output->writeln('Captcha file transfer Successfully');
                    }else{
                        $output->writeln('Captcha file already exist!');
                    }
                    break;
                case 'auth-service':
                    if($fs->exists('app/config/config_api.yml') === false){
                        $fs->mirror(__DIR__.'/../source/api', 'app/config');
                        $output->writeln('Service file transfer Successfully');
                    }else{
                        $output->writeln('Service file already exist!');
                    }
                    break;
                default:
                    $output->writeln('Command is incorrect');
            }
        } catch (IOExceptionInterface $e) {
            $output->writeln('An error occurred while creating file');
        }
    }
}