<?php
namespace cyw;

/**
 * Class Fedora
 * 
 * This class handles interactions with a Fedora repository, including creating containers,
 * ingesting RDF documents, and ingesting images.
 */
class Fedora {

  private array $config;

  /**
   * Fedora constructor.
   * 
   * Initializes the Fedora client configuration.
   * 
   * @param array $config The configuration array containing Fedora connection details.
   */
  public function __construct(array $config)
  {
    $this->config = $config;
  }

  /**
   * Create a new basic container within Fedora.
   * 
   * @param string $slug The slug to be used for the new container.
   * @return int The HTTP status code of the response.
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
   * Ingest RDF document to Fedora.
   * 
   * @param string $slug The slug to be used for the RDF document.
   * @param string $rdf The RDF document content.
   * @return int The HTTP status code of the response.
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