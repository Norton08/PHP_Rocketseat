<?php

namespace App\Livewire\Proposals;

use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
	public Project $project;

	public bool $modal = true;
	
	#[Validate(['required', 'email'])]
	public string $email = '';
	
	#[Validate(['required', 'numeric', 'gt:0'])]
	public int $hours = 0;

	public bool $agree = false;
    public function render()
    {
        return view('livewire.proposals.create');
    }

	public function save()
	{
		$this->validate();

		if(!$this->agree){
			$this->addError('agree', 'Você precisa concordar com os termos de uso!');
			return;
		}

		$this->dispatch('proposal::created');
		 
		$this->project->proposals()
			->updateOrCreate(
				['email' => $this->email],
				['hours' => $this->hours]
			);

		$this->modal = false;
	}
}
