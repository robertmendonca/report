<?php
# Arxview API sample php script using php CURL.

# Arxview application connection information.
$arxhost = '158.98.137.91';
# Replace these with the desired arxview user login and password.
# If you use an A/D user make sure the user is set as <user>@<domain> and
# change ldap below to 1.
$username = 'pti00721';
$password = 'IrgO69A.c*Q2.f4';
$user = "$username@activedir.service.lsb.esni.ibm.com";
$ldap = 1;
$prot = 'https';
$port = '443';
# For when http authentication is in use. Set to empty strings if not needed.
$huser = '';
$hpassword = '';

# This example uses JSON output format along with the PHP json_decode.
$format = 'json';

$apipath = "$prot://$arxhost:$port/api";
$options = array(
      CURLOPT_POST=>true,
      CURLOPT_RETURNTRANSFER=>true,
      CURLOPT_SSL_VERIFYPEER=>false,
      CURLOPT_SSL_VERIFYHOST=>false,
);
if ($huser != '' and $hpassword != '') $options[CURLOPT_USERPWD] = "$huser:$hpassword";
$ch = curl_init();
curl_setopt_array($ch, $options);

# Login
curl_setopt($ch, CURLOPT_URL, "$apipath/login.php");
curl_setopt($ch, CURLOPT_POSTFIELDS, array('u'=>$user, 'p'=>$password, 'l'=>$ldap));
$out = curl_exec($ch);
if (curl_errno($ch)) {
      print "Error making curl login request: " . curl_error($ch) . "\n";
      exit(1);
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status != 200) {
      print "Received HTTP status from login request: $status\n";
      exit(1);
}
curl_setopt($ch, CURLOPT_COOKIE, $out);

# The method to be called and its parameters. This is where you could
# add any optional parameters that may be allowed for the method.
$method = 'getNetAppCClusterCapacities';
$params = array('format'=>$format);

# API call with the method as a parameter named 'm'.
curl_setopt($ch, CURLOPT_URL, "$apipath/api.php");
$params['m'] = $method;
# Alternate syntax with method name in the URL.
#curl_setopt($ch, CURLOPT_URL, "$apipath/$method");
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

$out = curl_exec($ch);
if (curl_errno($ch)) {
      print "Error making curl API request: " . curl_error($ch) . "\n";
      exit(1);
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status != 200) {
      print "Received HTTP status from API request: $status. Outout was: $out\n";
      exit(1);
}

# Decode the JSON data into an array. This example simply prints the result.
$data = json_decode($out, true);
if ($data === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON content received from API']);
    exit(1);
}

$filtroArray = isset($_GET['array_name']) ? $_GET['array_name'] : null;
if (is_array($data) && $filtroArray !== null) {
    $data = array_filter($data, function ($item) use ($filtroArray) {
        return isset($item['array_name']) && $item['array_name'] === $filtroArray;
    });
    $data = array_values($data); // reindexa o array
}

$limited = array_slice($data, 0, 1000); // Limita aos 10 primeiros

header('Content-Type: application/json');
echo json_encode($limited, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

# Logout
curl_setopt($ch, CURLOPT_URL, "$apipath/logout.php");
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
$out = curl_exec($ch);
if (curl_errno($ch)) {
      print "Error making curl logout request: " . curl_error($ch) . "\n";
      exit(1);
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status != 200) {
      print "Received HTTP status from logout request: $status\n";
      exit(1);
}
