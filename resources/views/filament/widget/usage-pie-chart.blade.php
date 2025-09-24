@php
    $currentUser = auth()->user();
    $showChart = $currentUser && ($currentUser->hasRole('admin') || $currentUser->hasRole('manager'));
@endphp

@if ($showChart)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div 
        x-data 
        x-init="
            () => {
                const ctx = $refs.canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Used', 'Remaining'],
                        datasets: [{
                            data: [{{ $created }}, {{ $remaining }}],
                            backgroundColor: [
                                '{{ $percentUsed >= 100 ? '#f87171' : ($percentUsed >= 90 ? '#facc15' : '#22c55e') }}',
                                '#e5e7eb'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        "
        class="w-48 mx-auto mt-6"
    >
        <canvas x-ref="canvas" height="150"></canvas>
    </div>

    <div class="text-center mt-2 text-sm">
        <span class="font-semibold">{{ $created }}</span> of 
        <span class="font-semibold">{{ $maxLimit }}</span> used 
        ({{ number_format($percentUsed, 1) }}%)
    </div>

    @if($percentUsed >= 90 && $percentUsed < 100)
        <div class="text-center text-yellow-700 text-xs font-semibold mt-1">
            Warning: Almost at your limit.
        </div>
    @elseif($percentUsed >= 100)
        <div class="text-center text-red-700 text-xs font-semibold mt-1">
            You have reached your maximum limit!
        </div>
    @endif
@endif
