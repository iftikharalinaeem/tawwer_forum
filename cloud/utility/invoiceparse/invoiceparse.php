#!/usr/bin/env php
<?php
include('simple_html_dom.php');
$argfiles = $argv;
array_shift($argfiles);

$files = array();
if (!sizeof($argfiles)) {
    echo "No arguments\n";
    exit;
}

foreach ($argfiles as $argfile) {
    if (!file_exists($argfile)) {
        echo "File does not exist: {$argfile}\n";
        continue;
    }
    $files[] = $argfile;
}

if (sizeof($files) < sizeof($argfiles))
    exit;

// We start off empty, so we don't compare
$compare = null;
$servers = array();
$lbs = array();

$invoices = array();

foreach ($files as $file) {

    $invoice = array(
        'total' => 0,
        'categories' => array(),
        'types' => array()
    );

    $myservers = array();
    $mylbs = array();

    $html = file_get_html($file);

    $rows = $html->find('div.data_table_row');
    foreach ($rows as $row) {

        $type = trim($row->find('div.invoice-item-type-value div.textCellContent', 0)->innertext);
        $cost = trim(str_replace('$', '', $row->find('div.total-price', 0)->innertext));

        // Determine category
        switch ($type) {
            case 'Servers - Instance Usage':
                $category = 'servers-rent';
                break;

            case 'NG Servers - Instance Usage':
                $category = 'servers-rent';
                break;

            case 'Servers - Extra IP':
                $category = 'servers-ips';
                break;

            case 'Servers - Outbound Transfer':
                $category = 'servers-xfer';
                break;

            case 'NG Servers - Outbound Transfer':
                $category = 'servers-xfer';
                break;

            case 'Files - Outbound Transfer':
                $category = 'files-xfer';
                break;

            case 'Files - CDN Transfer':
                $category = 'files-xfer';
                break;

            case 'Files - Storage Fees':
                $category = 'files-storage';
                break;

            case 'Hosting Fees':
                $category = 'misc';
                break;

            case 'LB Hourly Usage':
                $category = 'lb-rent';
                break;

            case 'LB Average Connection':
                $category = 'lb-connections';
                break;

            case 'LB Outbound Transfer':
                $category = 'lb-xfer';
                break;

            case 'SSL-LB Usage':
                $category = 'lb-rent';
                break;

            case 'SSL-LB Outbound Transfer':
                $category = 'lb-xfer';
                break;

            case 'SSL-LB Average Connection':
                $category = 'lb-connections';
                break;

            default:
                echo "Unknown type: {$type}\n";
                $category = 'unknown';
                break;
        }

        $invoice['total'] += $cost;
        if (!array_key_exists($category, $invoice['categories']))
            $invoice['categories'][$category] = 0;

        $invoice['categories'][$category] += $cost;

        // Custom work per category
        switch ($category) {
            case 'servers-rent':
                $info = trim($row->find('div.item-name div.subtext', 0)->innertext);
                preg_match('`Server name: ([\w\d\.-]+)(\.|\b)`i', $info, $matches);
                $name = $matches[1];
                $myservers[$name] = $cost;

                // Server Type
                $servertype = 'unknown';
                $matched = preg_match('`(varnish|front|cache|db|sphinx|data|vanillicon)[\d]*\.cl[\d]+`i', $name, $matches);
                if ($matched)
                    $servertype = $matches[1];
                else {
                    echo "unknown server type: {$name} (\${$cost})\n";
                }

                if (!array_key_exists($servertype, $invoice['types']))
                    $invoice['types'][$servertype] = 0;
                $invoice['types'][$servertype] += $cost;
                break;

            case 'lb-rent':
                $info = $row->find('div.item-name div.subtext', 0)->innertext;
                preg_match('`LB name: ([\w\d\.-]+)(\.|\b)`i', $info, $matches);
                $name = $matches[1];
                $mylbs[$name] = $cost;
                break;
        }
    }

    // Output general info
    print_r($invoice);

    // Compare
    if ($compare) {

        $lastinvoicen = sizeof($invoices) - 1;
        $lastinvoice = $invoices[$lastinvoicen];

        echo "Category Cost breakdown:\n";
        ksort($invoice['categories']);
        foreach ($invoice['categories'] as $category => $catcost) {
            $lastcatcost = val($category, $lastinvoice['categories'], 0);
            $diffsign = $catcost > $lastcatcost ? '+' : '-';
            $diff = abs($catcost - $lastcatcost);
            echo sprintf(" %s %20s : %8.2f -> %-8.2f (%s $%.2f)\n", $diffsign, $category, $lastcatcost, $catcost, $diffsign, $diff);
        }
        echo "\n";

        echo "Server Cost breakdown:\n";
        ksort($invoice['types']);
        foreach ($invoice['types'] as $st => $stcost) {
            $laststcost = val($st, $lastinvoice['types'], 0);
            $diffsign = $stcost > $laststcost ? '+' : '-';
            $diff = abs($stcost - $laststcost);
            echo sprintf(" %s %20s : %8.2f -> %-8.2f (%s $%.2f)\n", $diffsign, $st, $laststcost, $stcost, $diffsign, $diff);
        }
        echo "\n";

        $deletedservers = array_diff_key($servers, $myservers);
        $newservers = array_diff_key($myservers, $servers);

        if (sizeof($deletedservers)) {
            // Note deleted servers
            echo "Deleted servers:\n";
            foreach ($deletedservers as $dsname => $dscost)
                echo " - {$dsname} (\${$dscost})\n";
            $dsreduce = array_sum($deletedservers);
            echo " reduced cost: \${$dsreduce}\n";
        }

        if (sizeof($newservers)) {
            // Note added servers
            echo "New servers:\n";
            foreach ($newservers as $nsname => $nscost)
                echo " + {$nsname} (\${$nscost})\n";
            $nsincrease = array_sum($newservers);
            echo " added cost: \${$nsincrease}\n";
        }

        echo "\n";
        $deletedlbs = array_diff_key($lbs, $mylbs);
        $newlbs = array_diff_key($mylbs, $lbs);

        // Note deleted loadbalancers
        if (sizeof($deletedlbs)) {
            echo "Deleted loadbalancers:\n";
            foreach ($deletedlbs as $dlname => $dlcost)
                echo " - {$dlname} (\${$dlcost})\n";
            $dlreduce = array_sum($deletedlbs);
            echo " reduced cost: \${$dlreduce}\n";
        }

        if (sizeof($newlbs)) {
            // Note added loadbalancers
            echo "New loadbalancers:\n";
            foreach ($newlbs as $nlname => $nlcost)
                echo " + {$nlname} (\${$nlcost})\n";
            $nlincrease = array_sum($newlbs);
            echo " added cost: \${$nlincrease}\n";
        }
    }
    if (is_null($compare))
        $compare = true;

    $servers = $myservers;
    $invoices[] = $invoice;
}

function val($key, &$collection, $default = false, $remove = false) {
    $result = $default;
    if (is_array($collection) && array_key_exists($key, $collection)) {
        $result = $collection[$key];
        if ($remove)
            unset($collection[$key]);
    } elseif (is_object($collection) && property_exists($collection, $key)) {
        $result = $collection->$key;
        if ($remove)
            unset($collection->$key);
    }

    return $result;
}
