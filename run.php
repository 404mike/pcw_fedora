<?php
require_once realpath(__DIR__.'./')."vendor/autoload.php";

use Dotenv\Dotenv;
use Monolog\Logger;
use cyw\HttpStatusCode;
use Monolog\Handler\StreamHandler;

/**
 * Class PCWIngest
 * 
 * This class handles the ingestion of RDF documents and images into a Fedora repository.
 * It reads configuration from a .env file, processes JSON files from a data directory,
 * and logs the process using Monolog.
 */
class PCWIngest {

  private array $config;
  private Logger $logger;
  private \CyW\Fedora $fedora;
  private \CyW\RDF $rdf;
  
  /**
   * PCWIngest constructor.
   * 
   * Initializes the environment, logger, configuration, and dependencies.
   * Starts the process of looping through JSON files for ingestion.
   */
  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $this->logger = new Logger('PCWIngest');
    $this->logger->pushHandler(new StreamHandler('logs/pcw.log', Logger::DEBUG));

    $this->setConfig();

    $this->rdf = new \CyW\RDF();
    $this->fedora = new \CyW\Fedora($this->config);

    $this->loopFilesToIngest();
  }

  /**
   * Set configuration from environment variables.
   * 
   * Loads Fedora configuration (URL, username, password) from the .env file.
   */
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
   * Loop through all JSON files in the ./data directory.
   * 
   * Processes each JSON file, extracts the necessary data, and initiates the ingestion process.
   */
  private function loopFilesToIngest(): void
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
   * Ingest RDF document to Fedora.
   * 
   * @param string $json The JSON string containing the RDF data.
   * @param array $images The array of images associated with the RDF data.
   * @param string $nid The unique identifier for the RDF data.
   */
  private function ingestRdf(string $json, array $images, string $nid): void
  {
    $rdf = $this->rdf->format($json);

    echo $rdf;
    die();
    $response = $this->fedora->ingestRdf("item_$nid", $rdf);
    
    if($response == HttpStatusCode::CREATED->value){
      $this->ingestImages($images, $nid);
    }else{
      $this->logger->error('Error creating item for nid: '.$nid);
    }
  }

  /**
   * Ingest images to Fedora.
   * 
   * @param array $files The array of image files to be ingested.
   * @param string $nid The unique identifier for the RDF data.
   */
  private function ingestImages(array $files, string $nid): void
  {
    foreach ($files as $key => $value) {
      $filename = $value['originalFilename'];
      $this->logger->info('Uploading media for nid: '.$nid.' with filename: '.$filename);
      $fedora = $this->fedora->ingestImages("item_$nid", "images/$filename", $filename);

      if($fedora != HttpStatusCode::CREATED->value){
        $this->logger->error('Error uploading media for nid: '.$nid);
      }
    }
  }
}

(new PCWIngest());