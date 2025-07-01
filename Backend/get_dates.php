<?php
header('Content-Type: application/json');

if (isset($_GET['month']) && isset($_GET['year']) && isset($_GET['day'])) {
    $month = $_GET['month'];
    $year = $_GET['year'];
    $day = strtolower($_GET['day']);
    
    function getDatesForDayInMonth($month, $year, $day) {
        $dates = [];
        $dayMap = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];
        
        if (!isset($dayMap[$day])) {
            return $dates;
        }
        
        $dayAbbr = $dayMap[$day];
        $date = new DateTime("first $day of $month $year");
        $currentMonth = $date->format('m');
        
        while ($date->format('m') === $currentMonth) {
            $dates[] = $date->format('j');
            $date->modify("next $day");
        }
        
        return $dates;
    }
    
    $dates = getDatesForDayInMonth($month, $year, $day);
    echo json_encode($dates);
} else {
    echo json_encode([]);
}
?>