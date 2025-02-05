<?php
require_once realpath(__DIR__.'./')."vendor/autoload.php";

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class PCWIngest {

  private Logger $logger;
  private array $config;
  private \CyW\Fedora $fedora;
  private \CyW\RDF $rdf;
  
  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $this->logger = new Logger('PCWIngest');
    $this->logger->pushHandler(new StreamHandler('logs/pcw.log', Logger::DEBUG));

    $this->setConfig();

    $this->rdf = new \CyW\RDF();
    $this->fedora = new \CyW\Fedora($this->config);

    $this->loopFiles();
  }

  private function setConfig(): void
  {
    $this->config = [
      'fedora' => [
        'url' => $_ENV['FEDORA_URL'],
        'username' => $_ENV['FEDORA_USERNAME'],
        'password' => $_ENV['FEDORA_PASSWORD']
      ]
    ];
  }

  /**
   * Loop all the JSON files in ./data
   */
  private function loopFiles(): void
  {
    $loop = 0;
    $files = glob('data/*.{json}', GLOB_BRACE);
    
    foreach($files as $file) {
      $json = file_get_contents($file);
      $data = json_decode($json, true);
      $nid = $data['id'];
 
      echo "\n***************\n";
      $info = "Loop $loop: Trying to create Fedora entry for nid: $nid";
      echo "$info\n";
      $this->logger->info($info);

      $this->ingestRdf($json, $data['files'], $nid);
      $loop++;
    }
  }

  /**
   * Ingest RDF document to Fedora
   * @param string $json
   * @param array $images
   * @param string $nid
   */
  private function ingestRdf(string $json, array $images, string $nid): void
  {
    $rdf = $this->rdf->format($json);

    echo $rdf;
    die();
    $response = $this->fedora->ingestRdf("item_$nid", $rdf);
    
    if($response == 201){
      $this->ingestImages($images, $nid);
    }else{
      $this->logger->error('Error creating item for nid: '.$nid);
    }
  }

  /**
   * Ingest images to Fedora
   * @param array $files
   * @param string $nid
   */
  private function ingestImages(array $files, string $nid): void
  {
    foreach ($files as $key => $value) {
      $filename = $value['originalFilename'];
      $this->logger->info('Creating image for nid: '.$nid.' with filename: '.$filename);
      $fedora = $this->fedora->ingestImages("item_$nid", "images/$filename", $filename);

      if($fedora != 201){
        $this->logger->error('Error creating image for nid: '.$nid);
      }
    }
  }
}

(new PCWIngest());