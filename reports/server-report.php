<?php
// Custom Racktables Report v.0.2
// List all server

// 2012-07-11 - Mogilowski Sebastian <sebastian@mogilowski.net>

$tabhandler['reports']['server'] = 'renderServerReport'; // register a report rendering function
$tab['reports']['server'] = 'Servers';                    // The title of the report tab

function renderServerReport()
{
  $aResult = array();
  $iTotal = 0;
  $sFilter = '{$typeid_4}'; # typeid_4 = Server

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

    $aResult[$Result['id']]['sOS'] = '';
    if ( isset( $attributes['4']['a_value'] ) )
        $aResult[$Result['id']]['sOS'] = $attributes['4']['a_value'];

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

    // IP Informations
    $aResult[$Result['id']]['ipV4List'] = getObjectIPv4AllocationList($Result['id']);
    $aResult[$Result['id']]['ipV6List'] = getObjectIPv6AllocationList($Result['id']);

    // Port (MAC) Informations
    $aResult[$Result['id']]['ports'] = getObjectPortsAndLinks($Result['id']);

    $iTotal++;
  }

  // Load stylesheet and jquery scripts
  echo '<link rel="stylesheet" href="extensions/jquery/themes/racktables/style.css" type="text/css"/>';
  echo '<script type="text/javascript" src="extensions/jquery/jquery-latest.js"></script>';
  echo '<script type="text/javascript" src="extensions/jquery/jquery.tablesorter.js"></script>';
  echo '<script type="text/javascript" src="extensions/jquery/picnet.table.filter.min.js"></script>';

  // Display the stat array
  echo "<h2>Server report ($iTotal)</h2><ul>";

  echo '<table id="reportTable" class="tablesorter">
          <thead>
            <tr>
              <th>Name</th>
              <th>MAC</th>
              <th>IP(s)</th>
              <th>Comment</th>
              <th>Contact</th>
              <th>Type</th>
              <th>OEM S/N</th>
              <th>HW Expire Date</th>
              <th>OS</th>
              <th>Location</th>
            </tr>
          </thead>
        <tbody>';

  foreach ($aResult as $id => $aRow)
  {
      echo '<tr>
              <td><a href="'. makeHref ( array( 'page' => 'object', 'object_id' => $id) )  .'">'.$aRow['sName'].'</a></td>
              <td>';

      foreach ( $aRow['ports'] as $portNumber => $aPortDetails ) {
          if (trim($aPortDetails['l2address']) != '')
              echo $aPortDetails['l2address'] . '<br/>';
      }

      echo '  </td>
              <td>';

      foreach ( $aRow['ipV4List'] as $key => $aDetails ) {
          if ( function_exists('ip4_format') )
      	      $key = ip4_format($key);
          if ( trim($key) != '')
              echo $key . '<br/>';
      }

      foreach ( $aRow['ipV6List'] as $key => $aDetails ) {
          if ( function_exists('ip6_format') )
              $key = ip6_format($key);
          if ( trim($key) != '')
              echo $key . '<br/>';
      }

      echo '</td>
            <td>'.$aRow['sComment'].'</td>
            <td>'.$aRow['sContact'].'</td>
            <td>'.$aRow['HWtype'].'</td>
            <td>'.$aRow['OEMSN'].'</td>
            <td>'.$aRow['HWExpDate'].'</td>
            <td>'.$aRow['sOS'].'</td>
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
                3: { sorter: "ipAddress" },
              }, sortList: [[0,0]] }
            )
            $("#reportTable").tableFilter();
          });
       </script>';
}

# Need for backward compatibility - Define function from Racktables version 0.20.x
if ( !function_exists('ip6_format') ) {

    function ip6_format ($ip_bin) {
        // maybe this is IPv6-to-IPv4 address?
        if (substr ($ip_bin, 0, 12) == "\0\0\0\0\0\0\0\0\0\0\xff\xff")
            return '::ffff:' . implode ('.', unpack ('C*', substr ($ip_bin, 12, 4)));

        $result = array();
        $hole_index = NULL;
        $max_hole_index = NULL;
        $hole_length = 0;
        $max_hole_length = 0;

        for ($i = 0; $i < 8; $i++) {
            $unpacked = unpack ('n', substr ($ip_bin, $i * 2, 2));
            $value = array_shift ($unpacked);
            $result[] = dechex ($value & 0xffff);
            if ($value != 0) {
                unset ($hole_index);
                $hole_length = 0;
            }
            else {
                if (! isset ($hole_index))
                $hole_index = $i;
                if (++$hole_length >= $max_hole_length) {
                    $max_hole_index = $hole_index;
                    $max_hole_length = $hole_length;
                }
            }
        }
        if (isset ($max_hole_index)) {
            array_splice ($result, $max_hole_index, $max_hole_length, array (''));
            if ($max_hole_index == 0 && $max_hole_length == 8)
                return '::';
            elseif ($max_hole_index == 0)
                return ':' . implode (':', $result);
            elseif ($max_hole_index + $max_hole_length == 8)
                return implode (':', $result) . ':';
        }
        return implode (':', $result);
    }

}
