<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *  App\Http\Controllers\ProjectController
 */
class ProjectApiTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        Session::start();
        Model::reguard();
    }

    public function test_invoice_project()
    {

        $p = Project::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Best Project',
            'task_rate' => 100,
        ]);

        $t = Task::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'project_id' => $p->id,
            'client_id' => $this->client->id,
            'time_log' => '[[1731391977,1731399177,"item description",true],[1731399178,1731499177,"item description 2", true]]',
            'description' => 'Top level Task Description',
        ]);

        $e = Expense::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'project_id' => $p->id,
            'amount' => 100,
            'public_notes' => 'Expensive Business!!',
            'should_be_invoiced' => true,
        ]);

        $data = [
            'action' => 'invoice',
            'ids' => [$p->hashed_id],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects/bulk', $data);

        $response->assertStatus(200);

        $arr = $response->json();

    }

    public function test_bulk_project_invoice_validation()
    {

        $p1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $c = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $p2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $c->id,
        ]);

        $data = [
            'ids' => [$p1->hashed_id, $p2->hashed_id],
            'action' => 'invoice',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects/bulk', $data);

        $response->assertStatus(422);

    }

    public function test_bulk_project_invoice_validation_passes()
    {

        $p1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $c = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $p2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $c->id,
        ]);

        $data = [
            'ids' => [$p1->hashed_id],
            'action' => 'invoice',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects/bulk', $data);

        $response->assertStatus(200);

    }

    public function test_create_project_with_null_task_rate()
    {

        $data = [
            'client_id' => $this->client->hashed_id,
            'name' => 'howdy',
            'task_rate' => null,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(0, $arr['data']['task_rate']);

    }

    public function test_create_project_with_null_task_rate2()
    {

        $data = [
            'client_id' => $this->client->hashed_id,
            'name' => 'howdy',
            'task_rate' => 'A',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(422);

        $arr = $response->json();

    }

    public function test_create_project_with_null_task_rate3()
    {

        $data = [
            'client_id' => $this->client->hashed_id,
            'name' => 'howdy',
            'task_rate' => '10',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(10, $arr['data']['task_rate']);

    }

    public function test_create_project_with_null_task_rate5()
    {

        $data = [
            'client_id' => $this->client->hashed_id,
            'name' => 'howdy',
            'task_rate' => '-10',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(0, $arr['data']['task_rate']);

    }

    public function test_create_project_with_null_task_rate4()
    {

        $data = [
            'client_id' => $this->client->hashed_id,
            'name' => 'howdy',
            'task_rate' => 10,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(10, $arr['data']['task_rate']);

    }

    public function test_project_includes_zero_count()
    {

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson("/api/v1/projects/{$this->project->hashed_id}?include=expenses,invoices,quotes");

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(0, count($arr['data']['invoices']));
        $this->assertEquals(0, count($arr['data']['expenses']));
        $this->assertEquals(0, count($arr['data']['quotes']));

    }

    public function test_project_includes()
    {
        $i = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->project->client_id,
            'project_id' => $this->project->id,
        ]);

        $e = Expense::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->project->client_id,
            'project_id' => $this->project->id,
        ]);

        $q = Quote::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->project->client_id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson("/api/v1/projects/{$this->project->hashed_id}?include=expenses,invoices,quotes");

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(1, count($arr['data']['invoices']));
        $this->assertEquals(1, count($arr['data']['expenses']));
        $this->assertEquals(1, count($arr['data']['quotes']));

    }

    public function test_project_validation_for_budgeted_hours_put()
    {

        $data = $this->project->toArray();
        $data['budgeted_hours'] = 'aa';

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson("/api/v1/projects/{$this->project->hashed_id}", $data);

        $response->assertStatus(422);

    }

    public function test_project_validation_for_budgeted_hours_put_null()
    {

        $data = $this->project->toArray();
        $data['budgeted_hours'] = null;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson("/api/v1/projects/{$this->project->hashed_id}", $data);

        $response->assertStatus(200);

    }

    public function test_project_validation_for_budgeted_hours_put_empty()
    {

        $data = $this->project->toArray();
        $data['budgeted_hours'] = '';

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson("/api/v1/projects/{$this->project->hashed_id}", $data);

        $response->assertStatus(200);

    }

    public function test_project_validation_for_budgeted_hours()
    {

        $data = [
            'name' => $this->faker->firstName(),
            'client_id' => $this->client->hashed_id,
            'number' => 'duplicate',
            'budgeted_hours' => null,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

    }

    public function test_project_validation_for_budgeted_hours2()
    {

        $data = [
            'name' => $this->faker->firstName(),
            'client_id' => $this->client->hashed_id,
            'number' => 'duplicate',
            'budgeted_hours' => 'a',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(422);

    }

    public function test_project_validation_for_budgeted_hours3()
    {

        $data = [
            'name' => $this->faker->firstName(),
            'client_id' => $this->client->hashed_id,
            'number' => 'duplicate',
            'budgeted_hours' => '',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/projects', $data);

        $response->assertStatus(200);

    }

    public function test_project_get_filter()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/projects?filter=xx');

        $response->assertStatus(200);
    }

    public function test_project_get()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/projects/' . $this->encodePrimaryKey($this->project->id));

        $response->assertStatus(200);
    }

    public function test_project_post()
    {
        $data = [
            'name' => $this->faker->firstName(),
            'client_id' => $this->client->hashed_id,
            'number' => 'duplicate',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/projects', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/projects/' . $arr['data']['id'], $data)->assertStatus(200);

        try {
            $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/projects', $data);
        } catch (ValidationException $e) {
            $response->assertStatus(302);
        }
    }

    public function test_project_post_filters()
    {
        $data = [
            'name' => 'Sherlock',
            'client_id' => $this->client->hashed_id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/projects', $data);

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/projects?filter=Sherlock');

        $arr = $response->json();

        $this->assertEquals(1, count($arr['data']));
    }

    public function test_project_put()
    {
        $data = [
            'name' => $this->faker->firstName(),
            'public_notes' => 'Coolio',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/projects/' . $this->encodePrimaryKey($this->project->id), $data);

        $response->assertStatus(200);
    }

    public function test_project_not_archived()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/projects/' . $this->encodePrimaryKey($this->project->id));

        $arr = $response->json();

        $this->assertEquals(0, $arr['data']['archived_at']);
    }

    public function test_project_archived()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->project->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/projects/bulk?action=archive', $data);

        $arr = $response->json();

        $this->assertNotNull($arr['data'][0]['archived_at']);
    }

    public function test_project_restored()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->project->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/projects/bulk?action=restore', $data);

        $arr = $response->json();

        $this->assertEquals(0, $arr['data'][0]['archived_at']);
    }

    public function test_project_deleted()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->project->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/projects/bulk?action=delete', $data);

        $arr = $response->json();

        $this->assertTrue($arr['data'][0]['is_deleted']);
    }
}
