<?php
require('ddbToMw.php');

$pattern = '#^https:\/\/www\.dndbeyond\.com\/profile\/\w+\/characters\/\d+$#';
$errors = false;
$ddbUrl = null;
$ddbJson = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    try
    {
        $ddbUrl = $_POST['ddbUrl'] ?? null;
        $ddbJson = $_POST['ddbJson'] ?? null;

        if (!$ddbJson && (!$ddbUrl || !preg_match($pattern, $ddbUrl)))
            $errors = true;

        if (!$errors && $ddbUrl)
        {
            $ddbJsonUrl = $ddbUrl . '/json';
        }

        if (!$errors && $ddbJson)
        {
            $mw = new ddbToMw($ddbJson);
            $mw->exportFile();
        }
    }
    catch (Exception $e)
    {
        //var_dump($e->getMessage());
        //die();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>DDB to MW</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
    <div class="container">
        <a class="navbar-brand" href="">DDB to MW</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ml-auto"></ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container">
    <div class="row">
        <div class="col-lg-12 text-center">
            <h1 class="mt-5">Convert your DDB Character to a MW Character</h1>
        </div>
    </div>



        <p class="lead alert alert-primary"><strong class="text-primary">1:</strong> Get your DDB character URL: https://www.dndbeyond.com/profile/username/characters/12345678</p>
        <form id="ddbUrlForm" method="post" class="mb-3">
            <div class="row">
                <div class="col-md-8">
                    <label for="ddbUrl" class="sr-only">Paste your DDB Character URL</label>
                    <input type="text" class="form-control" id="ddbUrl" name="ddbUrl" placeholder="Paste your DDB Character URL" value="<?php echo htmlentities($ddbUrl) ?>" required <?php if($ddbJsonUrl): ?>readonly<? endif; ?>>
                    <div class="invalid-feedback">
                        Enter a valid DDB character URL
                    </div>
                </div>
                <div class="col-md-4">
                    <?php if (!$ddbJsonUrl): ?>
                        <button type="submit" id="submitUrl" class="btn btn-success btn-block disabled" disabled>Paste DDB URL</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-success btn-block disabled" disabled>Done!</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>

    <?php if ($ddbJsonUrl): ?>

        <p class="lead alert alert-primary"><strong class="text-primary">2:</strong> Follow the URL to get your character's JSON file from DDB.</p>

        <p class="lead"><a id="jsonLink" href="<?php echo htmlentities($ddbJsonUrl); ?>" target="_blank"><?php echo htmlentities($ddbJsonUrl); ?></a></p>

        <div id="step3" style="display: none">
            <p class="lead alert alert-primary"><strong class="text-primary">3:</strong> Copy and Paste the JSON into the field below.</p>

            <form id="ddbJsonForm" method="post">
                <div class="row">
                    <div class="col-md-8">
                        <label for="ddbJson" class="sr-only">Paste your DDB JSON</label>
                        <textarea class="form-control" id="ddbJson" name="ddbJson" rows="3"></textarea>
                        <div class="invalid-feedback">
                            Enter valid DDB JSON
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" id="submitJson" class="btn btn-success btn-block disabled" disabled>Paste DDB JSON</button>
                        <a href="" class="btn btn-outline-warning btn-block">Start Over</a>

                    </div>
                </div>
            </form>

            <div class="final-message alert alert-success mt-3">
                <p class="lead">Once your file downloads you can import the file into a <strong>NEW</strong> MW character.</p>
                <p class="form-text text-muted">Note: MW does not support updating existing characters with JSON.</p>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Bootstrap core JavaScript -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    $(function()
    {
        $('.final-message').hide();
        $('#jsonLink').click(function(){$('#step3').show()});

        $('#ddbUrl').keyup(function()
        {
            try
            {
                var string = $(this).val();
                var pattern = new RegExp(/^https:\/\/www\.dndbeyond\.com\/profile\/\w+\/characters\/\d+/);

                if (pattern.test(string))
                {
                    $('#submitUrl').prop("disabled",false).removeClass('disabled').html('Go');
                    $(this).parent().find('.invalid-feedback').hide();
                }
                else
                {
                    disableSubmitUrl();
                    $(this).parent().find('.invalid-feedback').show();
                }


            }
            catch(err)
            {
                //err.message;
                disableSubmitUrl();
                $(this).parent().find('.invalid-feedback').show();
            }
        });

        if ($('#ddbUrl').length && $('#ddbUrl').val().length)
            $('#ddbUrl').keyup();

        $('#ddbJson').keyup(function()
        {
            try
            {
                var response=jQuery.parseJSON($(this).val());

                if(typeof response =='object')
                {
                    $('#submitJson').prop("disabled",false).removeClass('disabled').html('Convert to MW');
                    $(this).parent().find('.invalid-feedback').hide();
                    $('.final-message').hide();
                }
                else
                {
                    disableSubmitJson();
                    $(this).parent().find('.invalid-feedback').show();
                    $('.final-message').hide();
                }

            }
            catch(err)
            {
                //err.message;
                disableSubmitJson();
                $(this).parent().find('.invalid-feedback').show();
                $('.final-message').hide();
            }
        });

        $('#ddbJsonForm').submit(function(e) {
            e.preventDefault();
            this.submit();
            $('#ddbJson').val('');
            $('.final-message').show();
            disableSubmitJson();
        });

    });

    function disableSubmitUrl()
    {
        $('#submitUrl').prop("disabled",true).addClass('disabled').html('Paste DDB URL');
    }

    function disableSubmitJson()
    {
        $('#submitJson').prop("disabled",true).addClass('disabled').html('Paste DDB JSON');
    }

</script>

</body>

</html>