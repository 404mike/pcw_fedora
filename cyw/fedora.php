<?php
namespace cyw;

class Fedora {

  /**
   * Create a new basic container within Fedora
   * @param string $slug
   * @return int
   */
  public function createBasicContainer($slug)
  {
    $client = new \GuzzleHttp\Client(['base_uri' => 'http://localhost:8080/fcrepo/rest/']);

    $res = $client->request('POST', 'pcw', [
      'auth' => [
        'fedoraAdmin',
        'fedoraAdmin'
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
   * @param object $rdf
   * @return int
   */
  public function ingestRdf($slug, $rdf)
  {
    $client = new \GuzzleHttp\Client(['base_uri' => 'http://localhost:8080/fcrepo/rest/']);

    $res = $client->request('POST', 'pcw', [
      'auth' => [
        'fedoraAdmin',
        'fedoraAdmin'
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
   * @param string $filname
   * @return int
   */
  public function ingestImages($slug, $image, $filname)
  {
    if(!file_exists($image)) die('No image at '. $image);

    $client = new \GuzzleHttp\Client(['base_uri' => 'http://localhost:8080/fcrepo/rest/']);

    $res = $client->request('POST', 'pcw/'.$slug, [
      'auth' => [
        'fedoraAdmin',
        'fedoraAdmin'
      ],
      'headers' => [
        'Slug' => $filname,
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment',
        'filename' => $filname,
      ],
      'body' => file_get_contents($image)
      
    ]);

    return $res->getStatusCode();
  }

}