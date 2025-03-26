<?php
namespace cyw;

/**
 * Class RDF
 * 
 * This class handles the creation and manipulation of RDF data using the EasyRDF library.
 */
class RDF {

  private string $domain = 'https://www.peoplescollection.wales';

  /**
   * RDF constructor.
   * 
   * Initializes the RDF graph and sets the namespaces.
   */
  public function __construct() {
    $this->setNamespaces();
  }

  /**
   * Set commonly accepted namespaces for RDF.
   * 
   * @return void
   */
  private function setNamespaces(): void {
    \EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');
    \EasyRdf\RdfNamespace::set('dct', 'http://purl.org/dc/terms/');
    \EasyRdf\RdfNamespace::set('prov', 'http://www.w3.org/ns/prov#');
    \EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
    \EasyRdf\RdfNamespace::set('cc', 'http://creativecommons.org/ns#');
  }

  /**
   * Add a literal property with language tags to a resource.
   * 
   * @param \EasyRdf\Resource $resource The RDF resource to which the property will be added.
   * @param string $property The property to be added.
   * @param array $values The values to be added.
   * @param array $langMap The language tags for the values.
   * @return void
   */
  private function addLiteralWithLang(\EasyRdf\Resource $resource, string $property, array $values, array $langMap): void {
    foreach ($langMap as $lang => $langTag) {
      if (isset($values[$lang])) {
        $resource->addLiteral($property, $values[$lang], $langTag);
      }
    }
  }

  /**
   * Add a literal property to a resource.
   * 
   * @param \EasyRdf\Resource $resource The RDF resource to which the property will be added.
   * @param string $property The property to be added.
   * @param string|null $value The value of the property.
   * @param string $datatype The datatype of the property.
   * @return void
   */
  private function addLiteralProperty(\EasyRdf\Resource $resource, string $property, ?string $value, string $datatype): void {
    if (!empty($value)) {
      $resource->add($property, [
        'type' => 'literal',
        'datatype' => $datatype,
        'value' => $value
      ]);
    }
  }

  /**
   * Add multiple subjects to a resource.
   * 
   * @param \EasyRdf\Resource $resource The RDF resource to which the subjects will be added.
   * @param string $property The property to be added.
   * @param array $subjects The subjects to be added.
   * @return void
   */
  private function addSubjects(\EasyRdf\Resource $resource, string $property, array $subjects): void {
    foreach ($subjects as $subject) {
      $this->addLiteralProperty($resource, $property, $subject, 'http://www.w3.org/2001/XMLSchema#string');
    }
  }

  /**
   * Format JSON data into an RDF/XML string.
   * 
   * @param string $json The JSON string containing the data.
   * @return string The formatted RDF/XML string.
   * @throws \InvalidArgumentException If the JSON input is invalid or missing required data.
   */
  public function format(array $data): string {
    $graph = new \EasyRdf\Graph();
    
    if (empty($data['id'])) {
      throw new \InvalidArgumentException('Missing required data: id');
    }

    $nid = $data['id'];
    $descResource = $graph->resource("{$this->domain}/$nid#this", 'foaf:Description');

    // TODO: Add more properties to the RDF document
    // Updated: Use schema:ImageObject instead of pcw:Image
    $descResource->add('rdf:type', $graph->resource('schema:ImageObject'));

    // Add titles
    $this->addLiteralWithLang($descResource, 'dc:title', $data['title'], ['en' => 'en-GB', 'cy' => 'cy-GB']);

    // Add descriptions
    $this->addLiteralWithLang($descResource, 'dc:description', $data['description'], ['en' => 'en-GB', 'cy' => 'cy-GB']);

    // Add creator
    $this->addLiteralProperty($descResource, 'dct:creator', $data['creator'], 'http://www.w3.org/2001/XMLSchema#string');

    // Add owner (provenance)
    $this->addLiteralProperty($descResource, 'dct:provenance', $data['owner'], 'http://www.w3.org/2001/XMLSchema#string');

    // Add license
    if (!empty($data['license']['type'])) {
      $licenseResource = $graph->resource("schema:license");
      $descResource->add('dct:license', $licenseResource);
    }

    // Add rights and provenance
    if (!empty($data['copyright'][0])) {
      $copyright = $data['copyright'][0];
      if (!empty($copyright['type'])) {
        $rightsResource = $graph->resource("schema:license");
        $descResource->add('dct:rights', $rightsResource);
      }
      if (!empty($copyright['year'])) {
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
    $docResource = $graph->resource("{$this->domain}/node/$nid.rdf", 'foaf:Document');

    // Add topic and license
    $docResource->add('foaf:primaryTopic', $descResource);
    $docResource->add('cc:license', $graph->resource("http://creativecommons.org/licenses/by/4.0/"));
    $docResource->add('cc:attributionURL', $graph->resource("{$this->domain}/items/$nid"));
    $this->addLiteralProperty($docResource, 'cc:attributionName', "People's Collection Wales", 'http://www.w3.org/2001/XMLSchema#string');

    // Add created and modified timestamps
    if (!empty($data['created'])) {
      $this->addLiteralProperty($docResource, 'dct:created', date('Y-m-d\TH:i:s+01:00', $data['created']), 'http://www.w3.org/2001/XMLSchema#dateTime');
    }
    if (!empty($data['updated'])) {
      $this->addLiteralProperty($docResource, 'dct:modified', date('Y-m-d\TH:i:s+01:00', $data['updated']), 'http://www.w3.org/2001/XMLSchema#dateTime');
    }

    return $graph->serialise('rdfxml');
  }

  private function getMediaType($file)
  {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    finfo_close($finfo);
    return $mime;
  }
}