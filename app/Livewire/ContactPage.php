<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class ContactPage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $success = false;

    public function sendMessage(): void
    {
        $this->validate();

        // Aquí se podría implementar el envío de email o guardar en BD
        // Por ahora simulamos el éxito
        $this->success = true;

        $this->reset(['name', 'email', 'phone', 'message']);

        session()->flash('message', '¡Gracias por contactarnos! Te responderemos a la brevedad.');
    }

    public function render(): View
    {
        return view('livewire.contact-page');
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'message' => 'required|string|min:10',
        ];
    }
}
