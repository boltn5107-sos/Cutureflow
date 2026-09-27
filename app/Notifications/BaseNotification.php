<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification interne (base de données uniquement en V1).
 * Aucun SMS ni WhatsApp.
 */
abstract class BaseNotification extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title(),
            'message' => $this->message(),
            'icon' => $this->icon(),
            'tone' => $this->tone(),
            'url' => $this->url(),
        ], $this->context());
    }

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function url(): string;

    /** Données additionnelles (clé d'identification, commande liée…). */
    public function context(): array
    {
        return [];
    }

    public function icon(): string
    {
        return 'fa-regular fa-bell';
    }

    public function tone(): string
    {
        return 'bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200';
    }
}
