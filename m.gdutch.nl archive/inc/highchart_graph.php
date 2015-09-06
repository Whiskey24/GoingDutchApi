<?php

class HighchartGraph {

  var $title;
  var $graphtitle;
  var $subtitle;
  var $chart;
  var $renderTo;
  var $defaultSeriesType;
  var $colors;
  var $xAxis;
  var $xAxis_categories;
  var $yAxis;
  var $yAxis_min;
  var $yAxis_title;
  var $legend;
  var $legend_layout;
  var $legend_backgroundColor;
  var $legend_align;
  var $legend_verticalAlign;
  var $legend_x;
  var $legend_y;
  var $legend_floating;
  var $legend_shadow;
  var $plotOptions;
  var $plotOptions_column_pointPadding;
  var $plotOptions_column_groupPadding;
  var $plotOptions_column_borderWidth;
  var $plotOptions_column_stacking;
  var $plotOptions_column_shadow;
  var $series;
  var $clickThrough;
  var $formatFunction;
  var $zoomGraph;
  var $maxZoom;
  var $formatFunctionPie;
  var $tooltip;
  var $lang;
  var $rangeSelector;
  function HighchartGraph(
  $graphtitle = '', $subtitle = '', $renderTo = 'graph_container', $defaultSeriesType = 'column',
  //$colors = array ('#c5d02c', '#4cae4a'),
          $colors = array('#92CD00', '#2C6700'), $xAxis_categories = array(), $yAxis_min = 0, $yAxis_title = '', $legend_layout = 'vertical', $legend_backgroundColor = '#FFFFFF', $legend_align = '', $legend_verticalAlign = '', $legend_x = '', $legend_y = '', $legend_floating = '', $legend_shadow = true, $plotOptions_column_pointPadding = '', $plotOptions_column_groupPadding = '', $plotOptions_column_borderWidth = '', $plotOptions_column_stacking = 'normal', $plotOptions_column_shadow = false, $series = array(), $clickThrough = false, $formatFunction = false, $zoomGraph = false, $maxZoom = 0, $formatFunctionPie = false
  ) {
    $this->graphtitle = $graphtitle;
    $this->subtitle = $subtitle;
    $this->chart['renderTo'] = $renderTo;
    $this->chart['defaultSeriesType'] = $defaultSeriesType;
   //  $this->colors = $colors;
    $this->xAxis['categories'] = $xAxis_categories;

    if (!empty($yAxis_min))
      $this->yAxis['min'] = $yAxis_min;
    if (!empty($yAxis_title))
      $this->yAxis['title'] = $yAxis_title;

    $this->legend['layout'] = $legend_layout;
    $this->legend['backgroundColor'] = $legend_backgroundColor;
    $this->legend['shadow'] = $legend_shadow;

    if (!empty($legend_align))
      $this->legend['align'] = $legend_align;
    if (!empty($legend_verticalAlign))
      $this->legend['verticalAlign'] = $legend_verticalAlign;
    if (!empty($legend_x))
      $this->legend['x'] = $legend_x;
    if (!empty($legend_y))
      $this->legend['y'] = $legend_y;
    if (!empty($legend_floating))
      $this->legend['floating'] = $legend_floating;


    if (!empty($plotOptions_column_pointPadding))
      $this->plotOptions['column']['pointPadding'] = $plotOptions_column_pointPadding;
    if (!empty($plotOptions_column_groupPadding))
      $this->plotOptions['column']['groupPadding'] = $plotOptions_column_groupPadding;
    if (!empty($plotOptions_column_borderWidth))
      $this->plotOptions['column']['borderWidth'] = $plotOptions_column_borderWidth;
    $this->plotOptions['column']['stacking'] = $plotOptions_column_stacking;
    $this->plotOptions['column']['shadow'] = $plotOptions_column_shadow;

    $this->series = $series;

 //   $this->clickThrough = $clickThrough;
//    if ($clickThrough) {
//      $this->plotOptions['series']['cursor'] = 'pointer';
//      $this->plotOptions['series']['point'] = array('events' => array('click' => ''));
//    }

    $this->formatFunction = $formatFunction;
    $this->zoomGraph = $zoomGraph;
    $this->maxZoom = $maxZoom;
    $this->formatFunctionPie = $formatFunctionPie;
  }

  function toJSON($containsFunction = false) {
    $chart = $this->prepareExport();
    if (!$containsFunction)
      return json_encode($chart);
    else 
      return $this->json_encode_jsfunc($chart);
    
  }

  function toObject() {
    $chart = $this->prepareExport();
    return $chart;
  }

  function prepareExport() {

    if (empty($this->yAxis['title']['text']))
      $this->yAxis['title']['text'] = $this->yAxis_title;
    if (!empty($this->yAxis_min) && $this->yAxis_min != 0)
      $this->yAxis['min'] = $this->yAxis_min;
    if (empty($this->title['text']))
      $this->title['text'] = $this->graphtitle;
    if (empty($this->colors))
            unset ($this->colors);
    if (empty($this->xAxis_categories) )
            unset ($this->xAxis['categories']);

//    if ($this->clickThrough) {
//      $this->plotOptions['series']['cursor'] = 'pointer';
//      $this->plotOptions['series']['point'] = array('events' => array('click' => ''));
//    }

    if ($this->zoomGraph) {
      // zoom graph

      $this->chart['zoomType'] = 'x';
      $this->chart['spacingRight'] = 20;

      $this->xAxis['type'] = 'datetime';
      $this->xAxis['maxZoom'] = $this->maxZoom;
      $this->xAxis['title'] = array('text' => null);

      unset($this->chart['defaultSeriesType']);
      unset($this->xAxis['categories']);
      unset($this->plotOptions['column']);

      $this->plotOptions['area'] = array(
          'fillColor' => array(
              'linearGradient' => array(0, 0, 0, 450),
              'stops' => array(array(0, 'rgba(146, 201, 228,1)'), array(1, 'rgba(64,64,64,.5)'))
          ),
          'linewidth' => 1,
          'marker' => array(
              'enabled' => false,
              'states' => array(
                  'hover' => array('enabled' => true, 'radius' => 5)
              )
          ),
          'shadow' => false,
          'states' => array(
              'hover' => array('lineWidth' => 1)
          )
      );
    }
    
    
    
    if ($this->formatFunction) {
      
      $chart = array(
          'chart' => $this->chart,
         // 'colors' => $this->colors,
          'title' => $this->title,
          'subtitle' => $this->subtitle,
          'xAxis' => $this->xAxis,
          'yAxis' => $this->yAxis,
          'legend' => $this->legend,
          'plotOptions' => $this->plotOptions,
          'series' => $this->series,
        //  'clickThrough' => $this->clickThrough,
          'formatFunction' => true,
          'formatFunctionPie' => $this->formatFunctionPie,
      //    'tooltip' => array('formatter' => '')
          );
    } else {
      $chart = array(
          'chart' => $this->chart,
         // 'colors' => $this->colors,
          'title' => $this->title,
          'subtitle' => $this->subtitle,
          'xAxis' => $this->xAxis,
          'yAxis' => $this->yAxis,
          'legend' => $this->legend,
          'plotOptions' => $this->plotOptions,
          'series' => $this->series,
        

          //'clickThrough' => $this->clickThrough
              );
      if (!empty($this->tooltip))
              $chart['tooltip'] = $this->tooltip;
      if (!empty($this->rangeSelector))
              $chart['rangeSelector'] = $this->rangeSelector;
      
      
    }
    if (empty($chart['subtitle']))
      unset($chart['subtitle']);
    if ($this->zoomGraph || $this->series[0]['type'] == 'spline')
      unset($chart['colors']);
    
    return $chart;
  }
  
  function create_scatter() {
    unset($this->plotOptions['column']);
    unset($this->xAxis_categories);
    $this->chart['defaultSeriesType'] = 'scatter';
    $this->plotOptions['scatter']['marker'] = array('radius' => 5, 'states' => array('hover' => array('enabled' => true)));
    $this->plotOptions['scatter']['states'] = array('hover' => array('marker' => array('enabled' => false)));
  }
  
  function create_pie() {
    unset($this->plotOptions['column']);
    unset($this->xAxis_categories);
    $this->chart['defaultSeriesType'] = 'pie';
    $this->plotOptions['pie'] = array('allowPointSelect' => true, 'cursor' => "'pointer");    
  }
  
  //http://php.net/manual/en/function.json-encode.php
  function json_encode_jsfunc($input=array(), $funcs=array(), $level=0)
 {
  foreach($input as $key=>$value)
         {
          if (is_array($value))
             {
              $ret = $this->json_encode_jsfunc($value, $funcs, 1);
              $input[$key]=$ret[0];
              $funcs=$ret[1];
             }
          else
             {
              if (substr($value,0,10)=='function()')
                 {
                  $func_key="#".uniqid()."#";
                  $funcs[$func_key]=$value;
                  $input[$key]=$func_key;
                 }
             }
         }
  if ($level==1)
     {
      return array($input, $funcs);
     }
  else
     {
      $input_json = json_encode($input);
      foreach($funcs as $key=>$value)
             {
              $input_json = str_replace('"'.$key.'"', $value, $input_json);
             }
      return $input_json;
     }
 } 
  
  
  
}

?>