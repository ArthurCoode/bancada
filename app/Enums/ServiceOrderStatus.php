<?php

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Received = 'received';
    case InAnalysis = 'in_analysis';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case InRepair = 'in_repair';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recebido',
            self::InAnalysis => 'Em análise',
            self::AwaitingApproval => 'Aguardando aprovação',
            self::Approved => 'Aprovado',
            self::InRepair => 'Em reparo',
            self::Ready => 'Pronto',
            self::Delivered => 'Entregue',
            self::Rejected => 'Reprovado',
            self::Cancelled => 'Cancelado',
        };
    }

    /** Classes completas do Tailwind (escritas por extenso para o Vite encontrá-las). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Received => 'bg-slate-100 text-slate-700',
            self::InAnalysis => 'bg-blue-100 text-blue-800',
            self::AwaitingApproval => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-indigo-100 text-indigo-800',
            self::InRepair => 'bg-purple-100 text-purple-800',
            self::Ready => 'bg-green-100 text-green-800',
            self::Delivered => 'bg-emerald-100 text-emerald-900',
            self::Rejected => 'bg-rose-100 text-rose-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }
}