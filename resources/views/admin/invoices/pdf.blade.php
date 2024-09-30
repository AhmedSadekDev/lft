<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            .header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 4cm;
                /* Adjust the height as needed */
                background-color: #ccc;
                /* Set your header background color */
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 4cm;
                /* Adjust the height as needed */
                background-color: #ccc;
                /* Set your footer background color */
            }

            .print {
                height: 21.7cm;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .invoice {
                margin: 4cm 0 4.2cm;
            }
        }

        table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .print-btn {
            background-color: #007bff;
            color: #fff;
            font-family: 'Cairo', sans-serif;
            display: block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            cursor: pointer;
            margin: 1rem auto 0;
        }
    </style>
    <title>Invoice</title>
</head>

<body>
    <div class="print" id="printableArea" style="width: 21cm;margin: auto;padding: 0 5mm;border: 1px solid #5d5d5d;">
        <div class="header">
            <!-- Header content goes here -->
        </div>
        <!-- First page -->
        <div class="invoice" style="max-height: 21.7cm;overflow-y: hidden;">

            <h2 style="font-family: 'Cairo', sans-serif;text-align: start; margin-bottom: 0;">فواتير</h2>
            <div class="data_invoice">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="company" style="display: flex;align-items: center;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">
                            فاتورة
                            رقم : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">2024-001-001
                        </p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">
                            التاريخ :
                        </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">2024-07-19
                            13:59:54</p>
                    </div>
                </div>
            </div>
            <div class="data_invoice" style="border-top: 4px solid #000;">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="company" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">
                            اسم الشركة : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">
                            mollitia
                            sint atque</p>
                    </div>
                    <div class="company" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">
                            عناية : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">
                            at
                            voluptatibus laboriosam</p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-weight: 700;">
                            الخط الملاحي : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-size: .8rem;">
                            ar</p>
                    </div>
                </div>
            </div>
            <div class="data_invoice">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            رقم الحجز : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            24234234234</p>
                    </div>
                    <div class="company" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            رقم الشهادة : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            234234234234</p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            الرقم الضريبي : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            461</p>
                    </div>
                </div>
            </div>


            <div style="max-height: 14cm;overflow-y: hidden;margin-bottom: .25cm;">

                <table style="display: table;width: 100%;margin-top: 0.5rem;border-spacing: 0;border: 1px solid #000;">
                    <thead
                        style="background-color: #000;color:#fff;font-family: 'Cairo', sans-serif;font-size: .8rem;vertical-align: middle;">
                        <tr>
                            <th style="padding: 0.5rem;text-align: start">م</th>
                            <th style="padding: 0.5rem;text-align: start">تفاصيل الفاتورة</th>
                            <th style="padding: 0.5rem;text-align: start">التكلفة</th>
                        </tr>
                    </thead>
                    <tbody
                        style="font-family: 'Cairo', sans-serif;font-size: .8rem;text-align: center;vertical-align: middle;">
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">1</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم
                                            الحاوية : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">532452345</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            مقاس ونوع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">xl - xl2500</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تاريخ : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">2024-07-18</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم
                                            المصنع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Delmer Kshlerin</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            خروج : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            وجهة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تعتيق : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">800</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">2</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم
                                            الحاوية : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">4534534</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            مقاس ونوع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">xl - xl2500</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تاريخ : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">2024-07-18</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم
                                            المصنع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Delmer Kshlerin</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            خروج : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            وجهة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تعتيق : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">800</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">3</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم
                                            الحاوية : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">34534534</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            مقاس ونوع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">xl - xl2500</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تاريخ : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">2024-07-18</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم
                                            المصنع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Delmer Kshlerin</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            خروج : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            وجهة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تعتيق : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">800</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">4</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم
                                            الحاوية : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">34534534</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            مقاس ونوع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">xl - xl2500</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تاريخ : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">2024-07-18</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم
                                            المصنع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Delmer Kshlerin</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            خروج : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            وجهة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تعتيق : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">800</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">5</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم
                                            الحاوية : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">34534534</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            مقاس ونوع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">xl - xl2500</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تاريخ : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">2024-07-18</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم
                                            المصنع : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Delmer Kshlerin</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            خروج : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            وجهة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                    <div class="info" style="display: flex;width: 25%;justify-content: start;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            تعتيق : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">Investor
                                            Implementation Orchestrator</p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">800</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">6</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            الخدمة : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">tester Direct
                                            Assurance Executive</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">
                                            ملاحظات : </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0"></p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">100</td>
                        </tr>
                    </tbody>
                </table>

            </div>


            <div class="price" style="border-bottom: 2px solid #000;">
                <div class="info" style="display: flex;align-items: center;justify-content: start;flex-wrap: wrap;">
                    <div class="info" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            ضريبة القيمة المضافة (10%) : </p>
                        <p class="text"
                            style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            410</p>
                    </div>
                    <div class="info" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            ضريبة عامة (50%) : </p>
                        <p class="text"
                            style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            2050</p>
                    </div>
                    <div class="info" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            إجمالي الفاتورة قبل الضريبة : </p>
                        <p class="text"
                            style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            4100</p>
                    </div>
                    <div class="info" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            اجمالي الفاتورة بعد الضريبة : </p>
                        <p class="text"
                            style="width: fit-content;width: 15%;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">
                            2460</p>
                    </div>
                </div>
            </div>

        </div>
        <!-- First page -->

        <!-- Middle page(s) -->
        <!-- Middle page(s) -->


    </div>

    <div class="printAttachments" id="printableAreaAttachments"
        style="width: 21cm;margin: auto;padding: 0 5mm;border: 1px solid #5d5d5d;">
        <!-- First page -->
        <div class="invoice">
            <h2 style="font-family: 'Cairo', sans-serif;text-align: start; margin-bottom: 0;">ملحقات</h2>
            <div class="data_invoice">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="company" style="display: flex;align-items: center;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">
                            فاتورة
                            رقم : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">
                            2024-001-001
                        </p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">
                            التاريخ :
                        </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">2024-07-19
                            13:59:54</p>
                    </div>
                </div>
            </div>
            <div class="data_invoice" style="border-top: 4px solid #000;">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="company" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">
                            اسم الشركة : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">
                            mollitia
                            sint atque</p>
                    </div>
                    <div class="company" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">
                            عناية : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">
                            at
                            voluptatibus laboriosam</p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-weight: 700;">
                            الخط الملاحي : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-size: .8rem;">
                            ar</p>
                    </div>
                </div>
            </div>
            <div class="data_invoice">
                <div class="data"
                    style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
                    <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            رقم الحجز : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            24234234234</p>
                    </div>
                    <div class="company" style="display: flex;align-items: center;width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            رقم الشهادة : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            234234234234</p>
                    </div>
                    <div class="invoice_number" style="display: flex;align-items: center; width: 33.3%;">
                        <p class="title"
                            style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">
                            الرقم الضريبي : </p>
                        <p class="text"
                            style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">
                            461</p>
                    </div>
                </div>
            </div>
            <div style="margin-bottom: .25cm;">
                
                <table style="display: table;width: 100%;margin-top: 0.5rem;border-spacing: 0;border: 1px solid #000;">
                    <thead
                        style="background-color: #000;color:#fff;font-family: 'Cairo', sans-serif;font-size: .8rem;vertical-align: middle;">
                        <tr>
                            <th style="padding: 0.5rem;text-align: start">م</th>
                            <th style="padding: 0.5rem;text-align: start">تفاصيل الفاتورة</th>
                            <th style="padding: 0.5rem;text-align: start">التكلفة</th>
                        </tr>
                    </thead>
                    <tbody
                        style="font-family: 'Cairo', sans-serif;font-size: .8rem;text-align: center;vertical-align: middle;">
                        <tr>
                            <td style="border-top: 2px solid #f5f5f5;">7</td>
                            <td style="border-top: 2px solid #f5f5f5;">
                                <div class="detalis_container"
                                    style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                                    <div class="info"
                                        style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">الخدمة :
                                        </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0">tester Direct Assurance
                                            Executive</p>
                                    </div>
                                    <div class="info"
                                        style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                                        <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">ملاحظات :
                                        </p>
                                        <p class="text" style="margin-bottom: 0;margin-top: 0"></p>
                                    </div>
                                </div>
                            </td>
                            <td style="border-top: 2px solid #f5f5f5;">100</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- First page -->
    </div>

    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
</body>

</html>
