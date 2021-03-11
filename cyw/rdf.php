<?php
namespace cyw;

class RDF {

  private $domain = 'https://www.peoplescollection.wales';

  /**
   * 
   */
  public function format($json)
  {
    // decode JSON
    $data = json_decode($json,true);

    // item node id
    $nid = $data['id'];

    // ontologies
    \EasyRdf\RdfNamespace::set('pcw',  'https://www.peoplescollection.wales/ontologies/PCW#');
    \EasyRdf\RdfNamespace::set('cc',   'http://creativecommons.org/ns#');
    \EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
    \EasyRdf\RdfNamespace::set('dct',  'http://purl.org/dc/terms/');
  
    $graph = new \EasyRdf\Graph();
  
    $pcw_rdf_desc = $graph->resource('https://www.peoplescollection.wales/' . $nid . '#this', 'foaf:Description');
  
    // type
    $type = $graph->resource("https://www.peoplescollection.wales/ontologies/PCW#Image");
    $pcw_rdf_desc->add('rdf:type', $type);
  
    // Title
    if(isset($data['title']['en'])) {
      $pcw_rdf_desc->addLiteral('dct:title', $data['title']['en'] , "en-GB");
    }

    if(isset($data['title']['cy'])) {
      $pcw_rdf_desc->addLiteral('dct:title', $data['title']['cy'] , "cy-GB");
    }
  
    // creator
    $pcw_rdf_desc->add('dct:creator', 
        array(
        'type' => 'literal',
        'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
        'value' => $data['creator']
      )
    );
  
    // owner
    $pcw_rdf_desc->add('dct:provenance', 
        array(
        'type' => 'literal',
        'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
        'value' => $data['owner']
      )
    );
  
    // description
    if(isset($data['description']['en'])) {
      $pcw_rdf_desc->addLiteral('dct:description', $data['description']['en'] , "en-GB");
    }

    if(isset($data['description']['cy'])) {
      $pcw_rdf_desc->addLiteral('dct:description', $data['description']['cy'] , "cy-GB");
    }
    
    // license
    if(isset($data['license']['type'])) {
      $license = $graph->resource("https://www.peoplescollection.wales/ontologies/PCW#".$data['license']['type']);
      $pcw_rdf_desc->add('dct:license', $license);
    }
    
    // rights
    if(isset($data['copyright'][0]['type'])) {
      $rights = $graph->resource("https://www.peoplescollection.wales/ontologies/PCW#".$data['copyright'][0]['type']);
      $pcw_rdf_desc->add('dct:rights', $rights);
    }
  
    // rights date
    if(isset($data['copyright'][0]['year'])) {
      $pcw_rdf_desc->add('pcw:rightsDate', 
        array(
          'type' => 'literal',
          'datatype' => 'http://www.w3.org/2001/XMLSchema#gYear',
          'value' => $data['copyright'][0]['year']
        )
      );
    }
  
    // rights holder
    if(isset($data['copyright'][0]['holder']['en'])) {
      $pcw_rdf_desc->add('dct:rightsHolder', 
        array(
          'type' => 'literal',
          'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
          'value' => $data['copyright'][0]['holder']['en']
        )
      );
    }

    if(isset($data['copyright'][0]['holder']['cy'])) {
      $pcw_rdf_desc->add('dct:rightsHolder', 
        array(
          'type' => 'literal',
          'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
          'value' => $data['copyright'][0]['holder']['cy']
        )
      );
    } 
  
    // subject
    if(!empty($data['tags'])) {
      foreach($data['tags'] as $subK => $subV) {
        $pcw_rdf_desc->add('dct:subject', 
          array(
            'type' => 'literal',
            'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
            'value' => $subV
          )
        );    
      }
    }

    // what facet
    if(!empty($data['what'])) {
      foreach($data['what'] as $whatK => $whatV) {
        $pcw_rdf_desc->add('dct:subject', 
          array(
            'type' => 'literal',
            'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
            'value' => $whatV
          )
        );    
      }
    }

    // when facet
    if(!empty($data['when'])) {
      foreach($data['when'] as $whenK => $whenV) {
        $pcw_rdf_desc->add('dct:subject', 
          array(
            'type' => 'literal',
            'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
            'value' => $whenV
          )
        );    
      }
    }

    // location
    if(!isset($data['locations'][0])) {
      $latlon = $data['locations'][0]['lat'] . ',' . $data['locations'][0]['lon'];
      $pcw_rdf_desc->add('dct:coverage', $latlon);
    }
    
  
    // ********
  
    $pcw_rdf_doc = $graph->resource("www.peoplescollection.wales/node/$nid.rdf", 'foaf:Document');
  
    // topic
    $topic = $graph->resource("https://www.peoplescollection.wales/items/$nid#this");
    $pcw_rdf_doc->add('foaf:primaryTopic', $topic);
  
    // cc license
    $cc_license = $graph->resource("http://creativecommons.org/licenses/by/4.0/");
    $pcw_rdf_doc->add('cc:license', $cc_license);
  
    // cc attributionURL
    $cc_attributionURL = $graph->resource("https://www.peoplescollection.wales/items/$nid");
    $pcw_rdf_doc->add('cc:attributionURL', $cc_attributionURL);
  
    // cc attributionName
    $cc_attributionName = $graph->resource("https://www.peoplescollection.wales/items/$nid");
    $pcw_rdf_doc->add('cc:attributionURL', $cc_attributionURL);
  
    // cc attributionName
    $pcw_rdf_doc->add('cc:attributionName', 
      array(
        'type' => 'literal',
        'datatype' => 'http://www.w3.org/2001/XMLSchema#string',
        'value' => 'People\'s Collection Wales'
      )
    );  
  
    // created
    $created = date('Y-m-d\TG:i:s+1:00',$data['created']);
    $pcw_rdf_doc->add('dct:created', 
      array(
        'type' => 'literal',
        'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        'value' => $created
      )
    );  
  
    // modified
    $updated = date('Y-m-d\TG:i:s+1:00',$data['updated']);
    $pcw_rdf_doc->add('dct:modified', 
      array(
        'type' => 'literal',
        'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        'value' => $updated
      )
    ); 
  
    # Finally output the graph
    $data = $graph->serialise('rdfxml');
    
    return $data;
  }
}