<?php

namespace App\Notifications;

use App\Models\Mesa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MesaListaParaEntregarNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, int>  $ordenIds
     */
    public function __construct(
        public Mesa $mesa,
        public array $ordenIds,
        public string $entregaKey,
        public ?string $entregadoPor = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mesa_id' => $this->mesa->id,
            'mesa_numero' => $this->mesa->numero,
            'orden_ids' => array_values($this->ordenIds),
            'entrega_key' => $this->entregaKey,
            'titulo' => "Mesa {$this->mesa->numero} lista para entregar",
            'mensaje' => "Todos los platillos de la Mesa {$this->mesa->numero} están listos para llevarse a los comensales.",
            'entregado_por' => $this->entregadoPor,
            'entregado_at' => now()->toIso8601String(),
        ];
    }
}
