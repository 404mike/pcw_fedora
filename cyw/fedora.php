<?php
namespace cyw;

/**
 * Class Fedora
 * 
 * This class handles interactions with a Fedora repository, including creating containers,
 * ingesting RDF documents, and ingesting images.
 */
class Fedora {

  private string $projectId;

  /**
   * Fedora constructor.
   * 
   * Initializes the Fedora client configuration.
   */
  public function __construct()
  {
    $this->projectId = $_ENV['FEDORA_PROJECT_ID'];
  }

  /**
   * Create a new basic container within Fedora.
   * 
   * @param string $slug The slug to be used for the new container.
   * @return int The HTTP status code of the response.
   */
  public function createBasicContainer(string $slug): int
  {
    $client = new \GuzzleHttp\Client(['base_uri' => $_ENV['FEDORA_URL']]);

    $res = $client->request('POST', 'pcw', [
      'auth' => [
        $_ENV['FEDORA_USERNAME'],
        $_ENV['FEDORA_PASSWORD']
      ],
      'headers' => [
          'Slug' => $slug
      ]
    ]);

    return $res->getStatusCode();
  }

  /**
   * Ingest RDF document to Fedora.
   * 
   * @param string $slug The slug to be used for the RDF document.
   * @param string $rdf The RDF document content.
   * @return int The HTTP status code of the response.
   */
  public function ingestRdf(string $slug, string $rdf): int
  {
    $client = new \GuzzleHttp\Client(['base_uri' => $_ENV['FEDORA_URL']]);

    $res = $client->request('POST', $this->projectId, [
      'auth' => [
        $_ENV['FEDORA_USERNAME'],
        $_ENV['FEDORA_PASSWORD']
      ],
      'headers' => [
        'Slug' => $slug,
        'Content-Type' => 'application/rdf+xml',
      ],
      'body' => $rdf
    ]);

    return $res->getStatusCode();
  }

  /**
   * Ingest image to item endpoint.
   * 
   * @param string $slug The slug to be used for the image.
   * @param string $image The path to the image file.
   * @param string $filename The filename to be used for the image.
   * @return int The HTTP status code of the response.
   */
  public function ingestImages(string $slug, string $image, string $filename): int
  {
    if (!file_exists($image)) {
        throw new \InvalidArgumentException('No image at ' . $image);
    }

    $client = new \GuzzleHttp\Client(['base_uri' => $_ENV['FEDORA_URL']]);

    $res = $client->request('POST', $this->projectId . '/' . $slug, [
      'auth' => [
        $_ENV['FEDORA_USERNAME'],
        $_ENV['FEDORA_PASSWORD']
      ],
      'headers' => [
        'Slug' => $filename,
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment',
        'filename' => $filename,
      ],
      'body' => file_get_contents($image)
    ]);

    return $res->getStatusCode();
  }

  public static function checkIfFedoraObjectExists(string $nid): bool
  {
      $client = new \GuzzleHttp\Client(['base_uri' => $_ENV['FEDORA_URL']]);
  
      try {
          $response = $client->request('HEAD', 'pcw/' . $nid, [
              'auth' => [
                  $_ENV['FEDORA_USERNAME'],
                  $_ENV['FEDORA_PASSWORD']
              ]
          ]);
  
          return $response->getStatusCode() === 200;
  
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          // 404 means the resource doesn't exist — that's fine
          if ($e->getResponse()->getStatusCode() === 404) {
              return false;
          }
  
          // Re-throw other client errors (e.g. 403)
          throw $e;
  
      } catch (\GuzzleHttp\Exception\RequestException $e) {
          // Network errors or unexpected issues
          throw new \RuntimeException('Error checking Fedora object: ' . $e->getMessage(), 0, $e);
      }
  }

}