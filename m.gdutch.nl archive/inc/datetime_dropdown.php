<?php
    /**
    * http://www.phpro.org/examples/Dynamic-Date-Time-Dropdown-List.html
    * @Create dropdown of years
    * @param int $start_year
    * @param int $end_year
    * @param string $id The name and id of the select object
    * @param int $selected
    * @return string
    */
    

    
    function createYears($start_year, $end_year, $id='year_select', $selected=null)
    {

        /*** the current year ***/
        $selected = is_null($selected) ? date('Y') : $selected;

        /*** range of years ***/
        $r = range($start_year, $end_year);

        /*** create the select ***/
        //$select = '<select name="'.$id.'" id="'.$id.'">';
        $select = '<select class="selectdate" name="'.$id.'" id="'.$id.'">';
        
        
        foreach( $r as $year )
        {
            $select .= "<option value=\"$year\"";
            $select .= ($year==$selected) ? ' selected="selected"' : '';
            $select .= ">$year</option>\n";
        }
        //$select .= '</select>';
        $select .= '</select>';
        return $select;
    }


    function createYearsEvent($start_year, $end_year, $id='year_select', $selected=null)
    {

        /*** the current year ***/
        $selected = is_null($selected) ? date('Y') : $selected;

        /*** range of years ***/
        $r = range($start_year, $end_year);

        /*** create the select ***/
        //$select = '<select name="'.$id.'" id="'.$id.'">';
        $select = '<select class="selectdate" name="'.$id.'" id="'.$id.'">';
        
        
        foreach( $r as $year )
        {
            $select .= "<option value=\"$year\"";
            $select .= ($year==$selected) ? ' selected="selected"' : '';
            $select .= ">$year</option>\n";
        }
        //$select .= '</select>';
        $select .= '</select></span>';
        return $select;
    }

    
    
    /*
    * @Create dropdown list of months
    * @param string $id The name and id of the select object
    * @param int $selected
    * @return string
    */
    function createMonths($id='month_select', $selected=null)
    {
        /*** array of months ***/
        $months = array(
                1=>'January',
                2=>'February',
                3=>'March',
                4=>'April',
                5=>'May',
                6=>'June',
                7=>'July',
                8=>'August',
                9=>'September',
                10=>'October',
                11=>'November',
                12=>'December');

        /*** current month ***/
        $selected = is_null($selected) ? date('m') : $selected;

        //$select = '<select name="'.$id.'" id="'.$id.'">'."\n";
        $select = ' <select class="selectdate" name="'.$id.'" id="'.$id.'">'."\n";
        

        foreach($months as $key=>$mon)
        {
            $select .= "<option value=\"$key\"";
            $select .= ($key==$selected) ? ' selected="selected"' : '';
            $select .= ">$mon</option>\n";
        }
        $select .= '</select>';
        
        return $select;
    }

    function createMonthsEvent($id='month_select', $selected=null)
    {
        /*** array of months ***/
        /*$months = array(
                1=>'Jan.',
                2=>'Feb.',
                3=>'Mar.',
                4=>'Apr.',
                5=>'May',
                6=>'June',
                7=>'July',
                8=>'Aug.',
                9=>'Sept.',
                10=>'Oct.',
                11=>'Nov.',
                12=>'Dec.'); */

        $months = array(
                1=>1,
                2=>2,
                3=>3,
                4=>4,
                5=>5,
                6=>6,
                7=>7,
                8=>8,
                9=>9,
                10=>10,
                11=>11,
                12=>12);

                
        /*** current month ***/
        $selected = is_null($selected) ? date('m') : $selected;

        //$select = '<select name="'.$id.'" id="'.$id.'">'."\n";
        $select = ' <select class="selectdate" name="'.$id.'" id="'.$id.'">'."\n";
        

        foreach($months as $key=>$mon)
        {
            $select .= "<option value=\"$key\"";
            $select .= ($key==$selected) ? ' selected="selected"' : '';
            $select .= ">$mon</option>\n";
        }
        $select .= '</select>';
        
        return $select;
    }
    
    /**
    * @Create dropdown list of days
    * @param string $id The name and id of the select object
    * @param int $selected
    * @return string
    */
    function createDays($id='day_select', $selected=null)
    {
        /*** range of days ***/
        $r = range(1, 31);

        /*** current day ***/
        $selected = is_null($selected) ? date('d') : $selected;

        //$select = "<select name=\"$id\" id=\"$id\">\n";
        $select = " <li class=\"select\"> <select class=\"selectdate\" name=\"$id\" id=\"$id\">\n";
        foreach ($r as $day)
        {
            $select .= "<option value=\"$day\"";
            $select .= ($day==$selected) ? ' selected="selected"' : '';
            $select .= ">$day</option>\n";
        }
        $select .= '</select>';
        return $select;
    }

    function createDaysEvent($id='day_select', $selected=null)
    {
        /*** range of days ***/
        $r = range(1, 31);

        /*** current day ***/
        $selected = is_null($selected) ? date('d') : $selected;

        //$select = "<select name=\"$id\" id=\"$id\">\n";
        $select = " <li class=\"selectrow\"> <span class=\"name\">Event Date:</span><span class=\"eventright\"><select class=\"selectdate\" name=\"$id\" id=\"$id\">\n";
        foreach ($r as $day)
        {
            $select .= "<option value=\"$day\"";
            $select .= ($day==$selected) ? ' selected="selected"' : '';
            $select .= ">$day</option>\n";
        }
        $select .= '</select>';
        return $select;
    }
    
    
    /**
    * @create dropdown list of hours
    * @param string $id The name and id of the select object
    * @param int $selected
    * @return string
    */
    function createHours($id='hours_select', $selected=null)
    {
        /*** range of hours ***/
        //$r = range(1, 12);
        $r = range(0, 23);

        if (is_null($selected)) {
          // if we are close to the hour (i.e. 15.55) take next hour
          $min = date('i');
          $s = 0;
          if (abs($s - $min) > abs(15 - $min)) $s = 15;
          if (abs($s - $min) > abs(30 - $min)) $s = 30;
          if (abs($s - $min) > abs(45 - $min)) $s = 45;
          if ($s == 0 && $min > 45) $plus = 1;
        
        }
        
        /*** current hour ***/
        $selected = is_null($selected) ? (date('H')+$plus) : $selected;

        //$select = "<select name=\"$id\" id=\"$id\">\n";
        $select = " <select class=\"selectdate\" name=\"$id\" id=\"$id\">\n";
        foreach ($r as $hour)
        {
            $select .= "<option value=\"$hour\"";
            //$select .= "<option value=\"" . sprintf("%02d", $hour) . "\"";
            $select .= ($hour==$selected) ? ' selected="selected"' : '';
            //$select .= ">$hour</option>\n";
            $select .= ">" . sprintf("%02d", $hour) . "</option>\n";
        }
        $select .= '</select>:';
        return $select;
    }

    /**
    * @create dropdown list of minutes
    * @param string $id The name and id of the select object
    * @param int $selected
    * @return string
    */
    function createMinutes($id='minute_select', $selected=null)
    {
        /*** array of mins ***/
        $minutes = array(0, 15, 30, 45);
        $min = date('i');
        $s = 0;
        if (abs($s - $min) > abs(15 - $min)) $s = 15;
        if (abs($s - $min) > abs(30 - $min)) $s = 30;
        if (abs($s - $min) > abs(45 - $min)) $s = 45;
        $selected = in_array($selected, $minutes) ? $selected : $s;
        
        //$select = "<select name=\"$id\" id=\"$id\">\n";
        $select = " <select class=\"selectdate\" name=\"$id\" id=\"$id\">\n";
        
        foreach($minutes as $min)
        {
            $select .= "<option value=\"$min\"";
            $select .= ($min==$selected) ? ' selected="selected"' : '';
            $select .= ">".str_pad($min, 2, '0')."</option>\n";
        }
        $select .= '</select></li>';
        return $select;
    }

    /**
    * @create a dropdown list of AM or PM
    * @param string $id The name and id of the select object
    * @param string $selected
    * @return string
    */
    function createAmPm($id='select_ampm', $selected=null)
    {
        $r = array('AM', 'PM');

    /*** set the select minute ***/
        $selected = is_null($selected) ? date('A') : strtoupper($selected);

        $select = "<select name=\"$id\" id=\"$id\">\n";
        foreach($r as $ampm)
        {
            $select .= "<option value=\"$t\"";
            $select .= ($ampm==$selected) ? ' selected="selected"' : '';
            $select .= ">$ampm</option>\n";
        }
        $select .= '</select>';
        return $select;
    }
?>