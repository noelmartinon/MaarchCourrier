<?php

class PropertyUri extends propertyCMIS{

  public function PropertyUri( $required, $inherited, $propertyType,
        $cardinality, $updatability, $choices, $openChoice, $queryable,
        $orderable){
          
    $this->required = $required ;
    $this->inherited = $inherited ;
    $this->propertyType = $propertyType;
    $this->cardinality = $cardinality ;
    $this->updatability = $updatability ;
    $this->choices = $choices ;
    $this->openChoice = $openChoice ;
    $this->queryable = $queryable ;
    $this->orderable = $orderable;
    
  }

}
