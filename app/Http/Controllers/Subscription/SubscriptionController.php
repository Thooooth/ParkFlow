<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Exception;

final class SubscriptionController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::with('companies')->get();
        $company = Auth::user()->company;

        return Inertia::render('subscriptions/index', [
            'plans' => $plans,
            'company' => $company,
           // 'intent' => $company->createSetupIntent(),
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan);
        $company = Auth::user()->company;

        try {
            $company->newSubscription('default', $plan->stripe_price_id)
                ->create($request->payment_method);

            $company->update([
                'subscription_status' => 'active',
                'subscription_plan_id' => $plan->id,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Assinatura realizada com sucesso!');
        } catch (Exception $e) {
            return back()->withErrors(['message' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
        }
    }

    public function cancel()
    {
        $company = Auth::user()->company;

        try {
            $company->subscription('default')->cancel();

            return redirect()->route('subscriptions.index')
                ->with('success', 'Assinatura cancelada com sucesso!');
        } catch (Exception $e) {
            return back()->withErrors(['message' => 'Erro ao cancelar assinatura: ' . $e->getMessage()]);
        }
    }

    public function resume()
    {
        $company = Auth::user()->company;

        try {
            $company->subscription('default')->resume();

            return redirect()->route('subscriptions.index')
                ->with('success', 'Assinatura retomada com sucesso!');
        } catch (Exception $e) {
            return back()->withErrors(['message' => 'Erro ao retomar assinatura: ' . $e->getMessage()]);
        }
    }
}
