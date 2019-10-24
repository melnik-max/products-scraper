<?php


namespace core;


use GuzzleHttp\Client;
use models\Category;
use models\Ingredient;
use traits\Parsable;


class General
{
    use Parsable;

    private $proxyPool;
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 300,
            'curl' => [ CURLOPT_SSLVERSION => 1 ],
        ]);

        //         $params = require __DIR__ . '/../config/params.php';

        $params = require 'config/params.php';
        $this->proxyPool = new ProxyPool($params['proxiesLink']);
    }

    public function parseWholeSite()
    {
        $categoriesPage = $this->getHTML(ROOT, $this->proxyPool, $this->client);
        $categories = $categoriesPage->find('.widget-list__item');

        foreach ($categories as $category) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("Error: impossible to fork\n");
            } elseif ($pid) {
                $childProcesses[] = $pid;
            } else {
                $category = new Category($category);
                $categoryData = $category->parse();

                $ingredient = new Ingredient($categoryData[0], $categoryData[1]);
                $ingredient->parse($this->client, $this->proxyPool);

                exit();
            }
        }

        foreach ($childProcesses as $pid) {
            pcntl_waitpid($pid, $status);
        }

        exit("\nParsing finished\n");
    }

}