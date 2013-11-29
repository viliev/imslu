<?php
/*
 * IMSLU version 0.1-alpha
 *
 * Copyright © 2013 IMSLU Developers
 * 
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

//enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.inc.php';

if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';

$db = new CPDOinstance();

$page['title'] = 'Ping - Arping';
$page['file'] = 'ping_arping.php';

require_once dirname(__FILE__).'/include/page_header.php';

if (!empty($_GET['userid'])) {


    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");
    if($userid == 0) {
        
        header("Location: users.php");
        exit;
    }

#####################################################
    // Get IP info and payment
#####################################################

    $sql = 'SELECT ipaddress, vlan FROM static_ippool
            WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $ip_info = $sth->fetchAll(PDO::FETCH_ASSOC);

    if(!$ip_info) {

        header("Location: users.php");
        exit;
    }

    if (count($ip_info) > 1) {

        for ($i = 0; $i < count($ip_info); ++$i) {

            $ip[$ip_info[$i]['ipaddress']] = $ip_info[$i]['ipaddress'];
            $vlan[$ip_info[$i]['ipaddress']] = $ip_info[$i]['vlan'];
        }
    }
    else {
        $ip[$ip_info[0]['ipaddress']] = $ip_info[0]['ipaddress'];
        $vlan[$ip_info[0]['ipaddress']] = $ip_info[0]['vlan'];
    }

    settype($_GET['packetsize'], "integer");
    $packetsize = ($_GET['packetsize'] > 0 && $_GET['packetsize'] < 1025) ? $_GET['packetsize'] : 1024;

    settype($_GET['count'], "integer");
    $count = ($_GET['count'] > 0 && $_GET['count'] < 26) ? $_GET['count'] : 5;


    $ipaddress = (!empty($_GET['ipaddress']) && filter_var($_GET['ipaddress'], FILTER_VALIDATE_IP)) ? $_GET['ipaddress'] : $ip_info[0]['ipaddress'];

    if (!empty($_GET['resource']) && $_GET['resource'] == 'ping') {

        $resource = array('ping' => 'ping', 'arping' => 'arping');

        $cmd = "$PING -s $packetsize -c $count $ipaddress 2>&1";
    }
    else {

        $resource = array('arping' => 'arping', 'ping' => 'ping');
        $iface = ($USE_VLANS && !empty($vlan[$ipaddress])) ? $vlan[$ipaddress] : $IFACE_INTERNAL;

        $cmd = "$SUDO $ARPING -i $iface -c $count $ipaddress 2>&1";
    }


    echo
"    <form method=\"get\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <input type=\"hidden\" name=\"userid\" value=\"$userid\">
              <label style=\"margin: 1px 3px 1px;\">".combobox('input select', 'resource', null, $resource)."</label>
              <label style=\"margin: 1px 3px 1px;\">
               <input class=\"input\" type=\"text\" name=\"packetsize\" value=\"1024\" maxlength=\"3\" size=\"5\">
              </label>
              <label style=\"margin: 1px 3px 1px;\">
               <input class=\"input\" type=\"text\" name=\"count\" value=\"$count\" maxlength=\"2\" size=\"3\">
              </label>
              <label style=\"margin: 1px 3px 1px;\">".combobox('input select', 'ipaddress', $_GET['ipaddress'], $ip)."</label>
              <input type=\"submit\" value=\""._('start')."\">
            </th>
          </tr>
        </tbody>
      </table>
    </form>
      <table class=\"tableinfo\">
          <tr class=\"header_top\">
            <th>
              <label>".chars($_GET['ipaddress'])."</label>
            </th>
          </tr>
          <tr>
            <td>
              <textarea style=\"margin-top:10px; margin-left:17%; width: 64%; height: 270px;\">\n";


    $descriptorspec = array(
       0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
       1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
       2 => array("pipe", "w")    // stderr is a pipe that the child will write to
    );
    $cwd = '/tmp';

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, array());

    if (is_resource($process)) {
        
        flush();
        while ($s = fgets($pipes[1])) {

            echo $s;
            ob_flush();
            flush();
        }
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_value = proc_close($process);
    }


    echo
"              </textarea>
            </td>
          </tr>
      </table>\n";

    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
    header("Location: users.php");
}