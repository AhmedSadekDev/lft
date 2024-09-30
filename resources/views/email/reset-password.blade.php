<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
                integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
                crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;700&display=swap" rel="stylesheet">
        <style>
        @media(max-width:991px) {
                .Email_page .info_content {
                        width: 95%;
                        margin: auto;
                }
        }

        @media (max-width: 575px) {
                .Email_page .info_content {
                        padding: 30px 15px;
                }

                .Email_page .number_code {
                        flex-direction: column;
                }
        }
        </style>
</head>

<body>
    <div style="text-align: right;margin: 50px 0;font-family: 'Tajawal', sans-serif;" class="Email_page">
        <div class="">
            <div class="">
                <div class="">
                    <div class="logo" style="display: flex;
                        justify-content: center;
                        align-items: center;">
                        <img style="width: 250px;
                            height: 150px;
                            object-fit: contain;" src="{{asset('public/email-logo.png')}}" alt="error image" loadin="lazy">
                    </div>
                    <div class="info_content" style="box-shadow: 0px 1px 16px #00000012;border-radius: 20px;padding: 64px 53px 47px;width: 70%;margin:50px auto;font-size: 20px;margin-top: 50px;">
                        <div class="head" style="margin-bottom: 32px;">
                            <span style="font-size: 26px;font-weight: bold;">مرحبا</span>
                        </div>
                        <p>انت تتلقى هذا البريد الإلكتروني لأننا تلقينا طلب إعادة تعيين كلمة المرور لحسابك</p>
                        <div class="number_code" style="display: flex;
                            justify-content: flex-start;
                            align-items: center;
                            gap: 15px;
                            flex-direction: row-reverse;
                            margin: 20px 0;">
                            <span>الرجاء إدخال هذا الرمز</span>
                            <span class="code" style="
                                width: 116px;
                                height: 35px;
                                background-color: #59E0AC;
                                color: #FFF;
                                font-weight: bold;
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                border-radius: 50px;">{{$token}}
                            </span>
                        </div>
                        <p>لإعادة تعيين كلمة المرور الخاصة بك</p>
                        <p> ستنتهي صلاحية رابط إعادة تعيين كلمة المرور هذا خلال 60 دقيقة إذا لم
                        تطلب إعادة تعيين كلمة المرور ، فلا داعي لاتخاذ أي إجراء آخر</p>
                        <br />
                        <p>تحياتي</p>
                        <h2 style="font-size: 20px; font-weight: bold;">El Zimity</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
