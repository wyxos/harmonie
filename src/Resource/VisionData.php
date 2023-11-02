<?php

namespace Wyxos\Harmonie\Resource;

use Illuminate\Contracts\Support\Arrayable;

class VisionData implements Arrayable
{
    protected array $form = [];
    protected array $data = [];
    protected array $query = [];

    public function form(array $form): self
    {
        $this->form = $form;
        return $this;
    }

    public function list(array $list): self
    {
        $this->query = $list;
        return $this;
    }

    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'form' => $this->form,
            'data' => $this->data,
            'query' => $this->query,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function __invoke(): array
    {
        return $this->toArray();
    }
}
