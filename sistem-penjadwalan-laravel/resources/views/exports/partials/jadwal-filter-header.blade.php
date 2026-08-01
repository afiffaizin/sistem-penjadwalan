<table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 16px; text-align: left; color: #1e3a8a; border: none; background: none; padding: 0 0 2px 0; text-transform: uppercase;">
                JADWAL PERKULIAHAN
            </th>
        </tr>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 13px; text-align: left; color: #1e3a8a; border: none; background: none; padding: 0 0 6px 0; text-transform: uppercase;">
                JURUSAN KOMPUTER DAN BISNIS
            </th>
        </tr>
        @if(isset($filterLabels))
            @foreach($filterLabels as $label => $value)
                <tr>
                    <th style="font-weight: bold; font-size: 10px; text-align: left; color: #111827; border: none; background: none; padding: 1px 0; width: 110px;">
                        {{ $label }}
                    </th>
                    <th style="font-weight: bold; font-size: 10px; text-align: left; color: #111827; border: none; background: none; padding: 1px 0; width: 12px;">
                        :
                    </th>
                    <th colspan="4" style="font-weight: bold; font-size: 10px; text-align: left; color: #111827; border: none; background: none; padding: 1px 0;">
                        {{ $value }}
                    </th>
                </tr>
            @endforeach
        @endif
        <tr>
            <th colspan="6" style="border: none; background: none; padding: 0; height: 6px;"></th>
        </tr>
    </thead>
</table>
