<?php
// Custom Racktables Report v.0.2
// List all virtual machines

// 2012-07-11 - Mogilowski Sebastian <sebastian@mogilowski.net>

$tabhandler['reports']['switches'] = 'renderSwitchReport'; // register a report rendering function
$tab['reports']['switches'] = 'Switches';      // The title of the report tab

function renderSwitchReport()
{
    $aResult = array();
    $iTotal = 0;
    $sFilter = '{$typeid_8}'; # typeid_8 = Switches

    foreach (scanRealmByText ('object', $sFilter) as $Result)
    {
        $aResult[$Result['id']] = array();
        $aResult[$Result['id']]['sName'] = $Result['name'];

        // Create active links in comment
        $aResult[$Result['id']]['sComment'] = preg_replace("/(http:\/\/|(www\.))(([^(\s|,)<]{4,68})[^(\s|,)<]*)/", '<a href="http://$2$3" target="_blank">$1$2$4</a>', $Result['comment']);

        // Load additional attributes:
        $attributes = getAttrValues ($Result['id']);
        $aResult[$Result['id']]['sContact'] = '';
        if ( isset( $attributes['14']['a_value'] ) )
            $aResult[$Result['id']]['sContact'] = $attributes['14']['a_value'];

        $aResult[$Result['id']]['HWtype'] = '';
        if ( isset( $attributes['2']['a_value'] ) )
            $aResult[$Result['id']]['HWtype'] = $attributes['2']['a_value'];

        $aResult[$Result['id']]['OEMSN'] = '';
        if ( isset( $attributes['1']['a_value'] ) )
            $aResult[$Result['id']]['OEMSN'] = $attributes['1']['a_value'];

        $aResult[$Result['id']]['HWExpDate'] = '';
        if ( isset( $attributes['22']['value'] ) )
            $aResult[$Result['id']]['HWExpDate'] = date("Y-m-d",$attributes['22']['value']);

        $aResult[$Result['id']]['sOSVersion'] = '';
        if ( isset( $attributes['5']['a_value'] ) )
            $aResult[$Result['id']]['sOSVersion'] = $attributes['5']['a_value'];

    $aResult[$Result['id']]['sSlotNumber'] = 'unknown';
    if ( isset( $attributes['28']['a_value'] ) && ( $attributes['28']['a_value'] != '' ) )
        $aResult[$Result['id']]['sSlotNumber'] = $attributes['28']['a_value'];

    // Location
    $sRowName = 'unknown';
    $sRackName = 'unknown';
    if ( function_exists('getMountInfo') ) {
        $mountInfo = getMountInfo (array($Result['id']));

        if ( isset( $mountInfo[$Result['id']][0]["rack_name"] ) )
            $sRackName = $mountInfo[$Result['id']][0]["rack_name"];

        $sRowName = 'unknown';
        if ( isset( $mountInfo[$Result['id']][0]["row_name"] ) )
            $sRowName = $mountInfo[$Result['id']][0]["row_name"];
    }
    else {
        if ( isset( $Result["Row_name"] ) )
            $sRowName = $Result["Row_name"];

        if ( isset( $Result["Rack_name"] ) )
            $sRackName = $Result["Rack_name"];
    }

    # No mount information available - check for a container
    if ( ( $sRowName == 'unknown' ) && ( $sRackName == 'unknown' ) && ( $Result['container_id'] ) ) {
    	$sContainerName = '<a href="'. makeHref ( array( 'page' => 'object', 'object_id' => $Result['container_id']) )  .'">'.$Result['container_name'].'</a>';
    	if ( $aResult[$Result['id']]['sSlotNumber'] != 'unknown' )
    	    $aResult[$Result['id']]['sLocation'] = $sContainerName.': Slot '.$aResult[$Result['id']]['sSlotNumber'];
    	else
    	    $aResult[$Result['id']]['sLocation'] = $sContainerName;

    	# Get mount info of the container
    	$sContainerRowName = 'unknown';
        $sContainerRackName = 'unknown';

        if ( function_exists('getMountInfo') ) {
            $containerMountInfo = getMountInfo (array($Result['container_id']));

            if ( isset( $containerMountInfo[$Result['container_id']][0]["rack_name"] ) )
                $sContainerRackName = $containerMountInfo[$Result['container_id']][0]["rack_name"];

            if ( isset( $containerMountInfo[$Result['container_id']][0]["row_name"] ) )
                $sContainerRowName = $containerMountInfo[$Result['container_id']][0]["row_name"];

            $aResult[$Result['id']]['sLocation'] = $sContainerRowName.': '.$sContainerRackName . '<br/>' . $aResult[$Result['id']]['sLocation'];

        }
    }
    else {
        $aResult[$Result['id']]['sLocation'] = $sRowName.': '.$sRackName;
    }


        $iTotal++;
    }

    // Load stylesheet and jquery scripts
    echo '<link rel="stylesheet" href="extensions/jquery/themes/racktables/style.css" type="text/css"/>';
    echo '<script type="text/javascript" src="extensions/jquery/jquery-latest.js"></script>';
    echo '<script type="text/javascript" src="extensions/jquery/jquery.tablesorter.js"></script>';
    echo '<script type="text/javascript" src="extensions/jquery/picnet.table.filter.min.js"></script>';

    // Display the stat array
    echo "<h2>Switch report ($iTotal)</h2><ul>";

    echo '<table id="reportTable" class="tablesorter">
            <thead>
              <tr>
                <th>Name</th>
                <th>Comment</th>
                <th>Contact</th>
                <th>Type</th>
                <th>OEM S/N</th>
                <th>HW Expire Date</th>
                <th>OS Version</th>
                <th>Location</th>
               </tr>
             </thead>
           <tbody>';

    foreach ($aResult as $id => $aRow)
    {
        echo '<tr>
                <td><a href="'. makeHref ( array( 'page' => 'object', 'object_id' => $id) )  .'">'.$aRow['sName'].'</a></td>
                <td>'.$aRow['sComment'].'</td>
                <td>'.$aRow['sContact'].'</td>
                <td>'.$aRow['HWtype'].'</td>
                <td>'.$aRow['OEMSN'].'</td>
                <td>'.$aRow['HWExpDate'].'</td>
                <td>'.$aRow['sOSVersion'].'</td>
                <td>'.$aRow['sLocation'].'</td>
              </tr>';
    }

    echo '  </tbody>
          </table>';

    echo '<script type="text/javascript">
            $(document).ready(function()
              {
                $.tablesorter.defaults.widgets = ["zebra"];
                $("#reportTable").tablesorter(
                    { headers: {
                    }, sortList: [[0,0]] }
                );
                $("#reportTable").tableFilter();
              }
            );
          </script>';
}
