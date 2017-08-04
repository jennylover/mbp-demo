<?php
    require('vendor/autoload.php');
    use Aws\S3\S3Client;
    use Aws\Exception\AwsException;
    use Aws\CognitoIdentity\CognitoIdentityClient;
    use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

    $cognito = CognitoIdentityProviderClient::factory(['version' => 'latest', 'region' => 'ap-northeast-1']);

    try {
        $user = $cognito->getUser([
            'AccessToken' => $_COOKIE['aws-cognito-app-access-token']
        ]);
    } catch(\Exception  $e) {
        // an exception indicates the accesstoken is incorrect - $this->user will still be null
    }

    echo("[[[[[".$user."]]]]]");
    //echo("[[[[[".$user->get("Username")."]]]]]");

    if(!$user) {
        header('Location: login.php');
        exit;
    }

    $s3 = S3Client::factory(['version' => '2006-03-01', 'region' => 'ap-northeast-1']);
    $bucket_in = "mbp-trans-input";
    $bucket_out = "mbp-trans-output";
?>

<html>
    <head><meta charset="UTF-8"></head>
    <body>
        <h1>MBP upload example</h1>
<?php
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['userfile']) && $_FILES['userfile']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
        try {
            $result = $s3->putObject(array(
                'Bucket'       => $bucket_in,
                'Key'          => $_FILES['userfile']['name'],
                'SourceFile'   => $_FILES['userfile']['tmp_name'],
                //'Body'         => fopen($_FILES['userfile']['tmp_name'], 'r+'),
                //'ContentType'  => 'text/plain',
                //'ACL'          => 'public-read',
                // 'StorageClass' => 'REDUCED_REDUNDANCY',
                'Metadata'     => array(
                    'param1' => 'value 1',
                    'param2' => 'value 2'
                )
            ));
            //var_dump($result);
    ?>
        <p>Upload <?php echo($result['ObjectURL']); ?> successful :) transcoding is working. please reload your page after 5secs later.</p>
<?php
        } catch(Exception $e) {
?>
        <p>Upload error :(</p>
<?php
        }
    }
?>
        <h2>Upload a file</h2>
        <form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
            <input name="userfile" type="file"><input type="submit" value="Upload">
        </form>

        <table width="70%" align="center" border="1">
<?php
    try {
        $result = $s3->listObjects(array('Bucket' => $bucket_out, 'Delimiter' => '/'));

        foreach ($result['CommonPrefixes'] as $object) {
            echo("<tr>");
            echo("<td colspan='4'><img src='http://www.theisozone.com/images/icons/forum.png'> ".$object['Prefix']."</td>");
            echo("</tr>");
        }

        foreach ($result['Contents'] as $object) {
            echo("<tr>");
            echo("<td width='50%'><img src='https://www.yourgenome.org/modules/file/icons/video-x-generic.png'> ".$object['Key']."</td>");
            echo("<td width='30%'>".$object['LastModified'].['date']."</td>");
            echo("<td width='15%'>".$object['Size']." Bytes</td>");
            echo("<td width='5%' align='center'><a href='http://d231sc70bupmhp.cloudfront.net/".$object['Key']."' target='_new'><img src='http://www.zemra.de/images/03-media-logo/play-zemra.png'></td>");
            echo("</tr>");
        }
    } catch (S3Exception $e) {
        echo $e->getMessage() . "\n";
    }
?>
        <table>
    </body>
</html>
