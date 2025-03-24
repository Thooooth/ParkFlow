import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useForm } from '@inertiajs/react';

interface Plan {
    id: number;
    name: string;
    price: number;
    max_parking_lots: number;
    max_users: number;
    has_analytics: boolean;
    has_api_access: boolean;
    features: string[];
}

interface Props {
    plans: Plan[];
    company: {
        subscription?: {
            ends_at: string | null;
        };
    };
    intent: {
        client_secret: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Planos e Assinaturas',
        href: route('subscriptions.index'),
    },
];
export default function Index({ plans }: Props) {
    const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);
    const { post, processing } = useForm({
        plan: '',
        payment_method: '',
    });

    const handleSubmit: FormEventHandler = async (e) => {
        e.preventDefault();
        post(route('subscriptions.subscribe'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Planos e Assinaturas" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {plans.map((plan) => (
                                    <div
                                        key={plan.id}
                                        className={`border rounded-lg p-6 ${
                                            selectedPlan?.id === plan.id
                                                ? 'border-blue-500 ring-2 ring-blue-500'
                                                : 'border-gray-200'
                                        }`}
                                    >
                                        <h3 className="text-lg font-semibold">{plan.name}</h3>
                                        <p className="text-3xl font-bold mt-2">
                                            R$ {plan.price.toFixed(2)}/mês
                                        </p>
                                        <ul className="mt-4 space-y-2">
                                            <li>Até {plan.max_parking_lots} estacionamentos</li>
                                            <li>Até {plan.max_users} usuários</li>
                                            {plan.has_analytics && (
                                                <li>Analytics avançado</li>
                                            )}
                                            {plan.has_api_access && (
                                                <li>Acesso à API</li>
                                            )}
                                            {plan.features.map((feature, index) => (
                                                <li key={index}>{feature}</li>
                                            ))}
                                        </ul>
                                        <button
                                            type="button"
                                            onClick={() => setSelectedPlan(plan)}
                                            className="mt-6 w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition"
                                        >
                                            Selecionar Plano
                                        </button>
                                    </div>
                                ))}
                            </div>

                            {selectedPlan && (
                                <form onSubmit={handleSubmit} className="mt-8">
                                    <input
                                        type="hidden"
                                        name="plan"
                                        value={selectedPlan.id}
                                    />
                                    {/* Aqui você adicionará o componente de cartão de crédito do Stripe */}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600 transition"
                                    >
                                        {processing ? 'Processando...' : 'Assinar Agora'}
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
