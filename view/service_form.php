<!DOCTYPE html>
<!-- 
TEMPLATE NAME : Adminre - frontend
VERSION : 1.3.0
AUTHOR : JohnPozy
AUTHOR URL : http://themeforest.net/user/JohnPozy
EMAIL : pampersdry@gmail.com
LAST UPDATE: 2015/01/05

** A license must be purchased in order to legally use this template for your project **
** PLEASE SUPPORT ME. YOUR SUPPORT ENSURE THE CONTINUITY OF THIS PROJECT **
-->
<html class="frontend">
<!-- START Head -->
<?php include_once 'partial/head.php' ?>
<!--/ END Head -->

<!-- START Body -->
<body>
<!-- START Template Header -->
<?php include_once 'partial/menu.php'; ?>
<!--/ END Template Header -->

<!-- START Template Main -->
<section id="main" role="main">
    <!-- START jumbotron -->
    <section class="jumbotron jumbotron-bg7 nm" data-stellar-background-ratio="0.4" style="min-height:486px;">
        <!-- pattern + overlay -->
        <div class="overlay pattern pattern2"></div>
        <!--/ pattern + overlay -->
        <div class="container" style="padding-top:8%;padding-bottom:8%;" style="background: #444444;">
            <div class="row">

                <form id="addServiceForm" action="" onsubmit="return false;">
                    <table align="center" class="table table-responsive">
                        <tr id="verification_code_msg" style="display: none">
                            <td colspan="2">
                                <div id='msg'></div>
                            </td>
                        </tr>
                        <tr>
                            <td>Select Service :</td>
                            <td>
                                <select name="service_provider_id" id="service_provider_id" onchange="whatsAppPhoneNumberConvention()" class="form-control">
                                    <option value="">Select a Service</option>
                                    <?php
                                    if (@$services) {
                                        foreach ($services as $service) {
                                            if (!in_array($service['id'], ['3', '4', '5', '6', '7', '8'])) { ?>
                                                <option value="<?php echo $service['id'] ?>"><?php echo $service['name'] ?></option>
                                            <?php }
                                        }
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Service ID :</td>
                            <td><input type="service_user_id" id="service_user_id" class="form-control"/></td>
                        </tr>
                        <tr id="verification_code_tr" style="display: none">
                            <td>Verification Code :</td>
                            <td>
                                <input class="text-default" type="verification_code" id="verification_code"/>&nbsp;
                                <input class="text-default" id="resendTokenBtn" type="button" onclick="sendCode();" value="Resend Code"/>
                            </td>

                        </tr>
                        <tr id="verification_code_send">
                            <td>&nbsp;</td>
                            <td>
                                <input id="getVerifucationBtn" type="button" onclick="sendCode();" value="Get Verification Code" class="btn btn-success btn-block"/>
                            </td>
                        </tr>
                        <tr id="verification_code_submit" style="display: none">
                            <td>&nbsp;</td>
                            <td>
                                <input id="addServiceBtn" type="button" onclick="addService();" value="Submit You Code" class="btn btn-success btn-block"/>
                            </td>
                        </tr>

                    </table>
                </form>
            </div>
        </div>
    </section>
    <!--/ END jumbotron -->


    <!-- START To Top Scroller -->
    <a href="#" class="totop animation" data-toggle="waypoints totop" data-showanim="bounceIn" data-hideanim="bounceOut" data-offset="50%"><i class="ico-angle-up"></i></a>
    <!--/ END To Top Scroller -->
</section>
<!--/ END Template Main -->

<!-- START Template Footer -->
<?php include_once 'partial/footer.php' ?>
<!-- End Template Footer -->
<?php include_once 'partial/core_script.php' ?>

</body>
<!--/ END Body -->
<div style="display: none" id="page_url" value="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>"></div>

<script type="text/javascript">
    function whatsAppPhoneNumberConvention() {
        if ($("#service_provider_id option:selected").text() == "Whats App") {
            $("#service_user_id").attr("placeholder", "Phone number without '+' ");
        }

    }
    function disableAllInForm() {
        $('#addServiceForm').find("input,select").attr("disabled", "disabled");
    }
    function enableAllInform() {
        $('#addServiceForm').find("input,select").removeAttr("disabled", "disabled");
    }
    function sendCode() {
        $('#getVerifucationBtn').attr("disabled", "disabled");
        $("#verification_code_tr").css({'display': 'none'});
        $("#verification_code_msg").css({'display': 'none'});

        var url = $("#page_url").val();
        var service_provider_id = $("#service_provider_id").val();
        var service_user_id = $("#service_user_id").val();
        disableAllInForm();
        $.ajax({
            url: url + "?r=sendCode",
            method: "POST",
            data: {
                "service_provider_id": service_provider_id,
                "service_user_id": service_user_id
            },
            success: function (data) {
                data = JSON.parse(data);
                if (data.status) {
                    $("#verification_code_tr").css({'display': ''});
                    $("#verification_code_send").css({'display': 'none'});
                    $("#verification_code_submit").css({'display': ''});
                    $("#verification_code").removeAttr("disabled", "disabled");
                    $("#resendTokenBtn").removeAttr("disabled", "disabled");
                    $("#addServiceBtn").removeAttr("disabled", "disabled");
                }

                $("#msg").html(data.message);
                $("#verification_code_msg").css({'display': ''});
            }
        });
    }

    function addService() {
        $("#verification_code_msg").css({'display': 'none'});

        var url = $("#page_url").val();
        var service_provider_id = $("#service_provider_id").val();
        var service_user_id = $("#service_user_id").val();
        var token = $("#verification_code").val();

        $.ajax({
            url: url + "?r=submitCode",
            method: "POST",
            data: {
                "service_provider_id": service_provider_id,
                "service_user_id": service_user_id,
                "token": token
            },
            success: function (data) {
                var data = JSON.parse(data);
                if (data.status) {
                    window.location.href = 'dashboard' + phpSuffix + '?r=index';
                }

                $("#msg").html(data.message);
                $("#verification_code_msg").css({'display': ''});
            }
        });
    }
</script>
</html>