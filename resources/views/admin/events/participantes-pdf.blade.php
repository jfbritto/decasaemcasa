<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Participantes - {{ $event->city ?? $event->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 20mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 0;
            margin: 10;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            color: #4f46e5;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .stats {
            display: flex;
            margin-bottom: 15px;
        }
        .stats span {
            display: inline-block;
            margin-right: 20px;
            font-size: 11px;
        }
        .stats strong {
            color: #4f46e5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #4f46e5;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-confirmado {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-aprovado {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-pendente {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-fila_de_espera {
            background-color: #ffedd5;
            color: #9a3412;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
        .row-number {
            text-align: center;
            color: #999;
            width: 30px;
        }
        .checkbox {
            width: 40px;
            text-align: center;
        }
        .checkbox-box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #999;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista de Presença - {{ $event->city ?? $event->title }}</h1>
        <p>{{ $event->date->format('d/m/Y') }} | Capacidade: {{ $event->capacity ?: 'Ilimitada' }} | Total de inscritos: {{ $event->inscriptions->count() }}</p>
    </div>

    <div class="stats">
        <span><strong>{{ $event->inscriptions->where('status', 'confirmado')->count() }}</strong> Confirmados</span>
        <span><strong>{{ $event->inscriptions->where('status', 'aprovado')->count() }}</strong> Aprovados</span>
        <span><strong>{{ $event->inscriptions->where('status', 'fila_de_espera')->count() }}</strong> Fila de Espera</span>
        <span><strong>{{ $event->inscriptions->where('status', 'pendente')->count() }}</strong> Pendentes</span>
    </div>

    @if($event->inscriptions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th class="row-number">#</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>WhatsApp</th>
                    <th>Email</th>
                    <th>Cidade/Bairro</th>
                    <th>Status</th>
                    <th class="checkbox">Presente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($event->inscriptions as $index => $inscription)
                    <tr>
                        <td class="row-number">{{ $index + 1 }}</td>
                        <td><strong>{{ $inscription->full_name }}</strong></td>
                        <td>{{ $inscription->formatted_cpf }}</td>
                        <td>{{ $inscription->whatsapp }}</td>
                        <td>{{ $inscription->email }}</td>
                        <td>{{ $inscription->city_neighborhood }}</td>
                        <td>
                            <span class="status status-{{ $inscription->status }}">
                                {{ $inscription->status_label }}
                            </span>
                        </td>
                        <td class="checkbox"><span class="checkbox-box"></span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Nenhuma inscrição para este encontro.</p>
    @endif

    <div class="footer">
        Gerado em {{ now()->format('d/m/Y H:i') }} | De Casa em Casa
    </div>
</body>
</html>
