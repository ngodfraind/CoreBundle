<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/authorize.php';
include __DIR__ . '/libs.php';

$vendorDir = __DIR__ . "/../../vendor";
$logPreUpdate = $vendorDir . '/../app/logs/pre_update.log';
$logPostUpdate = $vendorDir . '/../app/logs/post_update.log';
@unlink($logPreUpdate);
@unlink($logPostUpdate);

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Claroline installer</title>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">
        <link rel="shortcut icon" href="../claroline.ico" />
        <script src="libs.js"></script>
        <script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
        <style>
            body { background: #E2E2E2; }
            .navbar {
                height: 53px;
                background: #428BCA;
                border-bottom: 3px solid #f89406;
                -webkit-box-shadow: 0 2px 3px rgba(0 ,0, 0 , 0.25);
                box-shadow: 0 2px 3px rgba(0, 0, 0, 0.25);
            }
            .navbar img { height: 35px; margin: 7px auto; }
            .required { color: #777; font-weight: normal; }
            .info-txt { margin: auto auto 24px; }
            .panel-body { max-width: 94%; margin: 24px; }
            .step-controls { margin: 14px 14% auto; }
        </style>
    </head>
    <body>
		<div id="data" data-locale='<?php echo isset($_GET['_locale']) ? $_GET['_locale']: 'en' ?>'></div>
        <nav class="navbar navbar-static-top navbar-inverse" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <img src="../uploads/logos/clarolineconnect.png"/>
                </div>
            </div>
        </nav>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Ceci est l'outil de mise à jour de claroline-connect
                        </div>
                        <div class="panel-body">
							<div class="well">
								Etapes de la mise à jour
								<ul>
									<li> créez un backup </li>
									<li> lancez le script de preupdate </li>
									<li> remplacez le dossier "vendor" </li>
									<li> lancez le script de post update </li>
								</ul>
							</div>
                            <a id="start-btn" class="btn btn-primary" data-toggle="modal" data-target="#upgrade-modal">
                                <?php Translator::translate('start'); ?>
                            </a>
                            <a id="return-btn" href=".." class="btn btn-danger">
                                <?php Translator::translate('return'); ?>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!----------------------- MODAL -------------------------------->

        <div class="modal fade" id="upgrade-modal">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title"></h4>
			  </div>
			  <div class="modal-body">
				  <div id="content-modal")>
				  </div>
				  <div id="log-container" class="row">
					<pre id="log-content" class="executable"
						 style="max-height: 150px; overflow: auto; display: none"
						 data-url="refresh.php">
					</pre>
				  </div>
			  </div>

			  <div class="modal-footer">
				<button id="execute-btn" type="button" class="btn btn-primary executable">Execute</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button id="back-btn" type="button" class="btn btn-primary">Back</button>
				<button id="next-btn" type="button" class="btn btn-primary">Next</button>
			  </div>
			</div>
		  </div>
		</div>

		<!---------------- END MODAL -------------------------------->

        <script>
			//installation steps
			var currentStep = 0;
			var steps = [
				{
					'title': 'backup_title',
					'content': 'backup_content',
					'action': null,
					'logFile': null
				},
				{
					'title': 'pre_update_title',
					'content': 'pre_update_content',
					'action': 'pre_update.php',
					'logFile': 'pre_update'
				},
				{
					'title': 'replace_vendor_title',
					'content': 'replace_vendor_content',
					'action': null,
					'logFile': null
				},
				{
					'title': 'post_update_title',
					'content': 'post_update_content',
					'action': 'post_update.php',
					'logFile': 'post_update'
				}
			];

			console.debug(steps);

			//initialize objects
			var translator   = new Translator();
			translator.setLocale($('#data').attr('data-locale'));
			var logDisplayer = new LogDisplayer('#log-content');

			var setStepExecutable = function() {
				$('.executable').show()
				logDisplayer.setLogFile(steps[currentStep].logFile);
				logDisplayer.start();

			};

			var setStepUnexecutable = function() {
				$('.executable').hide();
				logDisplayer.stop();
			};

			//initial modalbox content
			$('#content-modal').html(translator.translate(steps[currentStep].content));
			$('.modal-title').html(translator.translate(steps[currentStep].title));
			steps[currentStep].action ? setStepExecutable(): setStepUnexecutable();

			//event driven functions
			$('#next-btn').on('click', function(event) {
				currentStep = currentStep + 1 >= steps.length ? currentStep: currentStep += 1;
				steps[currentStep].action ? setStepExecutable(): setStepUnexecutable();
				$('#content-modal').html(translator.translate(steps[currentStep].content));
				$('.modal-title').html(translator.translate(steps[currentStep].title));
			});

			$('#back-btn').on('click', function(event) {
				currentStep = currentStep - 1 < 0 ? currentStep: currentStep -= 1;
				steps[currentStep].action ? setStepExecutable(): setStepUnexecutable();
				$('#content-modal').html(translator.translate(steps[currentStep].content));
				$('.modal-title').html(translator.translate(steps[currentStep].title));
			});

			$('#execute-btn').on('click', function(event) {
				var action = steps[currentStep].action;
				$.ajax({
					'url': action
				});
			});

			$('#upgrade-modal').on('hidden.bs.modal', function (e) {
				logDisplayer.stop();
			});

			$('#upgrade-modal').on('shown.bs.modal', function (e) {
				if (steps[currentStep].logFile) logDisplayer.start();

			});
        </script>
    </body>
</html>