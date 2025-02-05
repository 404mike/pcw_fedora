<?php
namespace cyw;

class Fedora {

  private array $config;

  public function __construct(array $config)
  {
    $this->config = $config;
  }

  /**
   * Create a new basic container within Fedora
   * @param string $slug
   * @return int
   */
  public function createBasicContainer(string $slug): int
  {
    $client = new \GuzzleHttp\Client(['base_uri' => $this->config['fedora']['url']]);

    $res = $client->request('POST', 'pcw', [
      'auth' => [
        $this->config['fedora']['username'],
        $this->config['fedora']['password']
      ],
      'headers' => [
          'Slug' => $slug
      ]
    ]);

    return $res->getStatusCode();
  }


  /**
   * Ingest RDF document to Fedora
   * @param string $slug
   * @param string $rdf
   * @return int
   */
  public function ingestRdf(string $slug, string $rdf): int
  {
    $client = new \GuzzleHttp\Client(['base_uri' => $this->config['fedora']['url']]);

    $res = $client->request('POST', 'pcw', [
      'auth' => [
        $this->config['fedora']['username'],
        $this->config['fedora']['password']
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
   * Ingest image to item endpoint
   * @param string $slug
   * @param string $image
   * @param string $filename
   * @return int
   */
  public function ingestImages(string $slug, string $image, string $filename): int
  {
    if(!file_exists($image)) die('No image at '. $image);

    $client = new \GuzzleHttp\Client(['base_uri' => $this->config['fedora']['url']]);

    $res = $client->request('POST', 'pcw/'.$slug, [
      'auth' => [
        $this->config['fedora']['username'],
        $this->config['fedora']['password']
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

}