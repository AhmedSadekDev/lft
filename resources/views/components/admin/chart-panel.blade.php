@props(['id', 'title', 'labels', 'series'])
<div class="lft-chart">
    <canvas id="{{ $id }}" role="img" aria-label="{{ $title }}" aria-describedby="{{ $id }}-data">{{ $title }} — القيم التفصيلية في الجدول التالي.</canvas>
</div>
<details class="lft-chart-data" id="{{ $id }}-data">
    <summary>عرض بيانات الرسم</summary>
    <div class="table-responsive" tabindex="0" role="region" aria-label="{{ $title }}">
        <table class="table no-datatable">
            <caption class="sr-only">{{ $title }}</caption>
            <thead><tr><th scope="col">الفترة</th>@foreach($series as $name => $values)<th scope="col">{{ $name }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach($labels as $index => $label)
                    <tr><th scope="row">{{ $label }}</th>@foreach($series as $values)<td class="lft-number">{{ $values[$index] }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
</details>
