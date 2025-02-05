<?php
namespace cyw;

class RDF {

  private string $domain = 'https://www.peoplescollection.wales';
  private array $graph;

  public function __construct() {
    $this->graph = new \EasyRdf\Graph();
    $this->setNamespaces();
  }

  private function setNamespaces(): void {
    // Set namespaces to commonly accepted ontologies
    \EasyRdf\RdfNamespace::set('cc', 'http://creativecommons.org/ns#');
    \EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
    \EasyRdf\RdfNamespace::set('dct', 'http://purl.org/dc/terms/');
    \EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');
    \EasyRdf\RdfNamespace::set('prov', 'http://www.w3.org/ns/prov#');
  }

  private function addLiteralWithLang(\EasyRdf\Resource $resource, string $property, array $values, array $langMap): void {
    foreach ($langMap as $lang => $langTag) {
      if (isset($values[$lang])) {
        $resource->addLiteral($property, $values[$lang], $langTag);
      }
    }
  }

  private function addLiteralProperty(\EasyRdf\Resource $resource, string $property, ?string $value, string $datatype): void {
    if (!empty($value)) {
      $resource->add($property, [
        'type' => 'literal',
        'datatype' => $datatype,
        'value' => $value
      ]);
    }
  }

  private function addSubjects(\EasyRdf\Resource $resource, string $property, array $subjects): void {
    foreach ($subjects as $subject) {
      $this->addLiteralProperty($resource, $property, $subject, 'http://www.w3.org/2001/XMLSchema#string');
    }
  }

  public function format(string $json): string {
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data['id'])) {
      throw new \InvalidArgumentException("Invalid JSON input or missing required data");
    }

    $nid = $data['id'];
    $descResource = $this->graph->resource("{$this->domain}/$nid#this", 'foaf:Description');

    // Updated: Use schema:ImageObject instead of pcw:Image
    $descResource->add('rdf:type', $this->graph->resource('schema:ImageObject'));

    // Add titles
    $this->addLiteralWithLang($descResource, 'schema:name', $data['title'], ['en' => 'en-GB', 'cy' => 'cy-GB']);

    // Add descriptions
    $this->addLiteralWithLang($descResource, 'schema:description', $data['description'], ['en' => 'en-GB', 'cy' => 'cy-GB']);

    // Add creator
    $this->addLiteralProperty($descResource, 'dct:creator', $data['creator'], 'http://www.w3.org/2001/XMLSchema#string');

    // Add owner (provenance)
    $this->addLiteralProperty($descResource, 'dct:provenance', $data['owner'], 'http://www.w3.org/2001/XMLSchema#string');

    // Add license
    if (!empty($data['license']['type'])) {
      $licenseResource = $this->graph->resource("schema:license");
      $descResource->add('dct:license', $licenseResource);
    }

    // Add rights and provenance
    if (!empty($data['copyright'][0])) {
      $copyright = $data['copyright'][0];
      if (!empty($copyright['type'])) {
        $rightsResource = $this->graph->resource("schema:license");
        $descResource->add('dct:rights', $rightsResource);
      }
      if (!empty($copyright['year'])) {
        // Updated: Use prov:generatedAtTime instead of pcw:rightsDate
        $this->addLiteralProperty($descResource, 'prov:generatedAtTime', $copyright['year'], 'http://www.w3.org/2001/XMLSchema#gYear');
      }
      foreach (['en', 'cy'] as $lang) {
        if (!empty($copyright['holder'][$lang])) {
          $this->addLiteralProperty($descResource, 'dct:rightsHolder', $copyright['holder'][$lang], 'http://www.w3.org/2001/XMLSchema#string');
        }
      }
    }

    // Add subjects and tags
    $this->addSubjects($descResource, 'dct:subject', $data['tags'] ?? []);
    $this->addSubjects($descResource, 'dct:subject', $data['what'] ?? []);
    $this->addSubjects($descResource, 'dct:subject', $data['when'] ?? []);

    // Add location
    if (!empty($data['locations'][0])) {
      $location = "{$data['locations'][0]['lat']},{$data['locations'][0]['lon']}";
      $descResource->add('schema:spatialCoverage', $location);
    }

    // Build the RDF document resource
    $docResource = $this->graph->resource("{$this->domain}/node/$nid.rdf", 'foaf:Document');

    // Add topic and license
    $docResource->add('foaf:primaryTopic', $descResource);
    $docResource->add('cc:license', $this->graph->resource("http://creativecommons.org/licenses/by/4.0/"));
    $docResource->add('cc:attributionURL', $this->graph->resource("{$this->domain}/items/$nid"));
    $this->addLiteralProperty($docResource, 'cc:attributionName', "People's Collection Wales", 'http://www.w3.org/2001/XMLSchema#string');

    // Add created and modified timestamps
    if (!empty($data['created'])) {
      $this->addLiteralProperty($docResource, 'dct:created', date('Y-m-d\TH:i:s+01:00', $data['created']), 'http://www.w3.org/2001/XMLSchema#dateTime');
    }
    if (!empty($data['updated'])) {
      $this->addLiteralProperty($docResource, 'dct:modified', date('Y-m-d\TH:i:s+01:00', $data['updated']), 'http://www.w3.org/2001/XMLSchema#dateTime');
    }

    return $this->graph->serialise('rdfxml');
  }
}