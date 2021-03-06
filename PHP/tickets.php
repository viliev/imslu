<?php
/*
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


require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$db = new PDOinstance();

####### PAGE HEADER #######
$page['title'] = 'Tickets';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

$sql = 'SELECT tickets.ticketid, tickets.userid, tickets.status, tickets.add, tickets.assign, users.name, users.address, users.phone_number, tickets.notes
        FROM `tickets`
        LEFT JOIN `users` ON tickets.userid = users.userid
        WHERE tickets.status = 1 ORDER BY `assign` DESC';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $rows[$i]['status'] = $ticket_status[$rows[$i]['status']];
    }

    $form =
"    <form name=\"tikets\" action=\"user_tickets_edit.php\" method=\"post\">
      <table>
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"8\">
              <label style=\"float: left;\">". _('total').": ".count($rows)."</label>
              <label>". _('tickets')."</label>
            </th>
          </tr> \n";

    $form .=
"          <tr class=\"header\">
            <th>"._('id')."</th>
            <th>"._('status')."</th>
            <th>"._('add')."</th>
            <th>"._('assign')."</th>
            <th>"._('name')."</th>
            <th>"._('address')."</th>
            <th>"._('phone')."</th>
            <th>"._('notes')."</th>
          </tr>
        </thead>
        <tbody>\n";

    if (isset($rows[0])) {

        for ($i = 0; $i < count($rows); ++$i) {

            $class = ($i % 2 == 0) ? "class=\"even_row\"" : "class=\"odd_row\"";
            $form .= 
"          <tr $class>
            <td><a class=\"bold\" href=\"user_tickets_edit.php?ticketid={$rows[$i]['ticketid']}\">{$rows[$i]['ticketid']}</a></td>
            <td>{$rows[$i]['status']}</td>
            <td>{$rows[$i]['add']}</td>
            <td>{$rows[$i]['assign']}</td>
            <td><a class=\"bold\" href=\"user.php?userid={$rows[$i]['userid']}\">".chars($rows[$i]['name'])."</a></td>
            <td>".chars($rows[$i]['address'])."</td>
            <td>".chars($rows[$i]['phone_number'])."</td>
            <td>".chars($rows[$i]['notes'])."</td>
          </tr> \n";
        }
    }

    $form .=          
"        </tbody>
      </table>
    </form> \n";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>