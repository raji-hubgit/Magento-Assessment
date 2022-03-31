<?php

namespace Custom\CustomerImport\Console\Command;

use Exception;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Custom\CustomerImport\Model\Customer;
use \Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

class CreateCustomers extends Command
{
   

    private $filesystem;

    private $customer;

    private $state;

    private $directoryList;

    private $file;


    public function __construct(
        Filesystem $filesystem,
        Customer $customer,
        State $state,
        DirectoryList $directoryList,
        File $file
    ) {
        parent::__construct();
        $this->filesystem    = $filesystem;
        $this->customer      = $customer;
        $this->state         = $state;
        $this->directoryList = $directoryList;
        $this->file          = $file;

    }//end __construct()


    public function configure(): void
    {
        $this->setName('customer:importer')->setDescription('Import Csv and json file of customer')->addOption('profile', null, InputOption::VALUE_REQUIRED, 'profile')->addArgument('path', InputArgument::REQUIRED, 'Enter path to CSV or JSON file in Magento directory');
        parent::configure();

    }//end configure()


    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);

            $profileType = $input->getOption('profile');

            $directoryPath = $input->getArgument('path');

            $filePath = $this->directoryList->getPath('var').'/'.$directoryPath;
            if (!file_exists($filePath)) {
                throw new LocalizedException(__('File'.$filePath.'does not exist!'));
            }

            $fileExtension = $this->file->getPathInfo($filePath);
            $extension       = $fileExtension['extension'];

            if ($profileType === 'csv' || $profileType === 'json') {
                $this->customer->install($filePath, $output, $extension);
            } else {
                throw new LocalizedException('The profile type should be csv or json');
            }

            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $output->writeln("<error>$msg</error>", OutputInterface::OUTPUT_NORMAL);
            return Cli::RETURN_FAILURE;
        }//end try

    }//end execute()


}//end class
