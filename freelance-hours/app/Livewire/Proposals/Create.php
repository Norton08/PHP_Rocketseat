<?php

namespace App\Livewire\Proposals;

use App\Actions\ArrangePositions;
use App\Models\Project;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
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

		DB::transaction(function() {
			$proposal = $this->project->proposals()
			->updateOrCreate(
				['email' => $this->email],
				['hours' => $this->hours]
			);
	
			$this->arrangePositions($proposal);
		});
		
		$this->dispatch('proposal::created');
		$this->modal = false;
	}

	public function arrangePositions(Proposal $proposal)
	{
		$query = DB::select("
			select 
				*,
				row_number() over (order by hours) as newPosition
			from proposals
			where project_id = {$proposal->project_id}
		");
		$position = collect($query)->where('id', "=", $proposal->id)->first();
		$otherProposal = collect($query)->where('position', "=", $position->newPosition)->first();

		if($otherProposal){
			$proposal->update(['position_status' => 'up']);
			Proposal::query()->where('id', '=', $otherProposal->id)->update(['position_status' => 'down']);
		}

		ArrangePositions::run($proposal->project_id);
	}
}
