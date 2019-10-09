<!DOCTYPE html>
<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/samplecode/controllers/pifController.php');
    $pif = new pifController();
?>
<html>
    <head>
        <title><?php echo $pif->properties['title']; ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href=<?php echo $pif->properties['css']; ?>>
    </head>
    <body>
    <div class="mainback">
        <div class="content" >
            <div class='samplearea'>
                <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular.min.js"></script>
                <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular-sanitize.js"></script>
                <script src=<?php echo $pif->properties['angularDrvPath']; ?> ></script>
                <div ng-app="pifApp" ng-controller="personCtrl">
                    <h3><a href="http://www.jasenw.net" title="Return to main site.">Profile</a> &Gg; Image Finder</h3>
                    <p>Get an image associated with any email address.</p>
                    <table>
                        <tr>
                            <td>
                                Email Address:<br/>
                                <input type="text" ng-model="email">
                            </td>
                            <td></td>
                            <td style="text-align: right;">
                                <input type="button" value="Submit" ng-click="callService()"/>
                            </td>
                        </tr>
                        <tr>
                        <hr>
                            <td colspan="2">
                                <p>{{jarMessage}}</p>
                                <div class="fullContactImage"><img class="portrait" ng-src="{{pifAppReply}}" alt='Image of Associated Email User' /></div>
                                <p>Image provided by <a href="https://www.fullcontact.com/" target="_blank">Full Contact</a></p>
                            </td>
                        </tr>
                    </table>
                    <p class="systemNotification">{{jRevision}} by Jasen Ward.</p>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>


