<?php

namespace Custom\CustomerImport\Model;

use Exception;
use Generator;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Custom\CustomerImport\Model\Import\CustomerImport;
use Magento\Framework\Filesystem\DriverInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Customer
{

    private $file;

    private $storeManagerInterface;

    private $customerImport;

    private $output;

    private $driver;

    private $jsonSerialize;

    public function __construct(
        File $file,
        StoreManagerInterface $storeManagerInterface,
        CustomerImport $customerImport,
        DriverInterface $driver,
        SerializerInterface $jsonSerialize
    ) {
        $this->file                  = $file;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerImport        = $customerImport;
        $this->driver                = $driver;
        $this->jsonSerialize         = $jsonSerialize;

    }//end __construct()


    public function install(string $filePath, OutputInterface $output, string $extension): void
    {
        $this->output = $output;

        // get store and website ID
        $store     = $this->storeManagerInterface->getStore();
        $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
        $storeId   = (int) $store->getId();

        if($extension=='csv') {
            // read the csv header
            $header = $this->readCsvHeader($filePath)->current();

            // read the csv file and skip the first (header) row
            $row = $this->readCsvRows($filePath, $header);
            $row->next();

            // while the generator is open, read current row data, create a customer and resume the generator
            while ($row->valid()) {
                $data = $row->current();
                $this->createCustomer($data, $websiteId, $storeId);
                $row->next();
            }
        }

        if($extension=='json'){
            $content = $this->driver->fileGetContents($filePath);
            $result = $this->jsonSerialize->unserialize($content);
            foreach ($result as $key => $keyValue){
                foreach ($keyValue as $key => $value){
                    $customerVal[$key] = $value;
                }
                $this->createCustomer($customerVal,$websiteId,$storeId);
            }
        }

    }//end install()


    private function readCsvRows(string $file, array $header): ?Generator
    {
        $handle = fopen($file, 'rb');

        while (!feof($handle)) {
            $data    = [];
            $rowData = fgetcsv($handle);
            if ($rowData) {
                foreach ($rowData as $key => $value) {
                    $data[$header[$key]] = $value;
                }

                yield $data;
            }
        }

        fclose($handle);

    }//end readCsvRows()


    private function readCsvHeader(string $file): ?Generator
    {
        $handle = fopen($file, 'rb');

        while (!feof($handle)) {
            yield fgetcsv($handle);
        }

        fclose($handle);

    }//end readCsvHeader()


    private function createCustomer(array $data, int $websiteId, int $storeId): void
    {
        try {
            // collect the customer data
            $customerData = [
                'email'                     => $data['email'],
                '_website'                  => 'base',
                '_store'                    => 'default',
                'confirmation'              => null,
                'dob'                       => null,
                'firstname'                 => $data['firstname'],
                'gender'                    => null,
                'group_id'                  => null,
                'lastname'                  => $data['lastname'],
                'middlename'                => null,
                'password_hash'             => null,
                'prefix'                    => null,
                'store_id'                  => $storeId,
                'website_id'                => $websiteId,
                'password'                  => null,
                'disable_auto_group_change' => 0,
                'some_custom_attribute'     => 'some_custom_attribute_value',
            ];

            // Save the customer data.
            $this->customerImport->importCustomerData($customerData);
        } catch (Exception $e) {
            $this->output->writeln(
                '<error>'.$e->getMessage().'</error>',
                OutputInterface::OUTPUT_NORMAL
            );
        }//end try

    }//end createCustomer()


}//end class
