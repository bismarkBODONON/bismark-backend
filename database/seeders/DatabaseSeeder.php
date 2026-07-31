<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Evaluation;
use App\Models\Incident;
use App\Models\IncidentStatusHistory;
use App\Models\Intervention;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Solution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    private int $incidentCounter = 1;

    public function run(): void
    {
        // ---------- Administrateur ----------
        $admin = User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@supportpro.com',
            'password' => 'password',
            'role' => 'admin',
            'phone' => '+33 1 23 45 67 89',
        ]);

        // ---------- Techniciens ----------
        $martin = User::create([
            'name' => 'Martin Dupont',
            'email' => 'martin.dupont@supportpro.com',
            'password' => 'password',
            'role' => 'technicien',
            'phone' => '+33 6 12 34 56 78',
        ]);

        $julie = User::create([
            'name' => 'Julie Bernard',
            'email' => 'julie.bernard@supportpro.com',
            'password' => 'password',
            'role' => 'technicien',
            'phone' => '+33 6 22 33 44 55',
        ]);

        $karim = User::create([
            'name' => 'Karim Haddad',
            'email' => 'karim.haddad@supportpro.com',
            'password' => 'password',
            'role' => 'technicien',
            'phone' => '+33 6 33 44 55 66',
        ]);

        // ---------- Solutions logicielles ----------
        $gestionCommerciale = Solution::create([
            'name' => 'Gestion Commerciale',
            'version' => '2.1.0',
            'description' => 'Suivi des ventes, devis et facturation clients.',
        ]);

        $gestionRH = Solution::create([
            'name' => 'Gestion RH',
            'version' => '3.4.2',
            'description' => 'Gestion des employés, congés et paie.',
        ]);

        $gestionStock = Solution::create([
            'name' => 'Gestion de Stock',
            'version' => '1.8.1',
            'description' => 'Suivi des stocks et inventaires multi-entrepôts.',
        ]);

        $crmEntreprise = Solution::create([
            'name' => 'CRM Entreprise',
            'version' => '1.6.0',
            'description' => 'Gestion de la relation client et du pipeline commercial.',
        ]);

        // Techniciens référents par solution
        $gestionCommerciale->technicians()->attach([$martin->id, $julie->id]);
        $gestionRH->technicians()->attach([$julie->id]);
        $gestionStock->technicians()->attach([$martin->id, $karim->id]);
        $crmEntreprise->technicians()->attach([$karim->id]);

        // ---------- Entreprises clientes ----------
        $entrepriseDemo = $this->createCompany('Entreprise Demo', 'espace.client@demo.com', 'Alice Moreau', '+33 1 40 50 60 70', '9 rue de la Paix, 75002 Paris');
        $entrepriseDemo->solutions()->attach([$gestionCommerciale->id, $gestionRH->id, $gestionStock->id]);

        $societeAlpha = $this->createCompany('Société Alpha', 'contact@alpha.fr', 'Nicolas Petit', '+33 4 78 90 12 34', '12 avenue Foch, 69006 Lyon');
        $societeAlpha->solutions()->attach([$gestionCommerciale->id, $crmEntreprise->id]);

        $societeBeta = $this->createCompany('Société Bêta', 'contact@beta.fr', 'Camille Girard', '+33 3 88 12 34 56', '5 place Kléber, 67000 Strasbourg');
        $societeBeta->solutions()->attach([$gestionRH->id]);

        $groupeDelta = $this->createCompany('Groupe Delta', 'contact@groupedelta.fr', 'Thomas Roux', '+33 5 56 78 90 12', "3 cours de l'Intendance, 33000 Bordeaux");
        $groupeDelta->solutions()->attach([$gestionStock->id, $gestionCommerciale->id]);

        // ---------- Incidents ----------
        $this->createIncident($entrepriseDemo, $gestionCommerciale, $martin,
            'Erreur lors de la génération du rapport',
            "Impossible de générer le rapport de ventes mensuel, une erreur 500 s'affiche.",
            'Bug', 'elevee', 'en_traitement', 5);

        $this->createIncident($entrepriseDemo, $gestionRH, $julie,
            'Problème de connexion',
            "Certains employés n'arrivent plus à se connecter à leur espace RH depuis ce matin.",
            'Bug', 'moyenne', 'pris_en_charge', 6);

        $this->createIncident($entrepriseDemo, $gestionCommerciale, $martin,
            'Bug affichage tableau de bord',
            'Les widgets du tableau de bord ne se chargent pas correctement sur Firefox.',
            'Bug', 'elevee', 'en_traitement', 7);

        $this->createIncident($entrepriseDemo, $gestionStock, null,
            "Lenteur lors de l'import des données",
            "L'import du fichier CSV de stock prend plus de 10 minutes, c'était instantané avant.",
            'Anomalie', 'moyenne', 'analyse', 8);

        $this->createIncident($entrepriseDemo, $gestionRH, $julie,
            "Problème d'export PDF",
            "L'export des fiches de paie en PDF génère un fichier vide.",
            'Bug', 'faible', 'cloture', 10, withEvaluation: true, rating: 5, comment: 'Très bonne réactivité, problème résolu rapidement.');

        $this->createIncident($societeAlpha, $gestionCommerciale, $martin,
            'Doublons dans la liste des devis',
            'Certains devis apparaissent en double dans le tableau de suivi.',
            'Bug', 'moyenne', 'resolu', 4);

        $this->createIncident($societeAlpha, $crmEntreprise, $karim,
            'Question sur les permissions utilisateurs',
            "Comment restreindre l'accès au module commercial pour un profil junior ?",
            'Question', 'faible', 'cloture', 12, withEvaluation: true, rating: 4, comment: 'Réponse claire, merci.');

        $this->createIncident($societeBeta, $gestionRH, $julie,
            'Erreur de calcul des congés payés',
            'Le solde de congés affiché ne correspond pas au calcul attendu pour plusieurs employés.',
            'Anomalie', 'critique', 'en_traitement', 2);

        $this->createIncident($societeBeta, $gestionRH, null,
            "Demande d'amélioration : export Excel",
            'Pourrait-on ajouter un export Excel en plus du PDF pour les bulletins de paie ?',
            'Amélioration', 'faible', 'declare', 1);

        $this->createIncident($groupeDelta, $gestionStock, $karim,
            'Alerte stock incorrecte',
            'Les alertes de stock bas se déclenchent alors que le stock est suffisant.',
            'Bug', 'elevee', 'pris_en_charge', 3);

        $this->createIncident($groupeDelta, $gestionCommerciale, $martin,
            'Facture non générée automatiquement',
            "La génération automatique de facture après validation d'un devis ne fonctionne plus.",
            'Bug', 'critique', 'resolu', 6);

        $this->createIncident($groupeDelta, $gestionStock, null,
            'Question sur la gestion multi-entrepôts',
            "Est-il possible de transférer du stock entre deux entrepôts directement depuis l'interface ?",
            'Question', 'faible', 'declare', 0);

        // ---------- Notifications de démonstration ----------
        Notification::create([
            'user_id' => $entrepriseDemo->user_id,
            'type' => 'resolution',
            'title' => 'Incident résolu',
            'message' => 'Votre incident a été résolu.',
            'read_at' => Carbon::now()->subDays(9),
        ]);

        Notification::create([
            'user_id' => $entrepriseDemo->user_id,
            'type' => 'message',
            'title' => 'Nouveau message',
            'message' => 'Vous avez reçu un nouveau message du technicien.',
        ]);

        Notification::create([
            'user_id' => $martin->id,
            'type' => 'assignation',
            'title' => 'Nouvel incident assigné',
            'message' => 'Un incident vous a été assigné sur Gestion Commerciale.',
        ]);

        $this->command->info('Comptes de démonstration créés (mot de passe : password) :');
        $this->command->table(['Rôle', 'Email'], [
            ['Administrateur', $admin->email],
            ['Technicien', $martin->email],
            ['Technicien', $julie->email],
            ['Technicien', $karim->email],
            ['Entreprise cliente', $entrepriseDemo->user->email],
            ['Entreprise cliente', $societeAlpha->user->email],
            ['Entreprise cliente', $societeBeta->user->email],
            ['Entreprise cliente', $groupeDelta->user->email],
        ]);
    }

    private function createCompany(string $name, string $email, string $contactName, string $phone, string $address): Company
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => 'entreprise',
            'phone' => $phone,
        ]);

        return Company::create([
            'user_id' => $user->id,
            'name' => $name,
            'contact_name' => $contactName,
            'phone' => $phone,
            'address' => $address,
        ]);
    }

    private function nextCode(): string
    {
        return sprintf('INC-%s-%04d', Carbon::now()->format('Y'), $this->incidentCounter++);
    }

    /**
     * Crée un incident et fait progresser son statut de façon cohérente,
     * en générant l'historique, les interventions, les messages et
     * l'évaluation associés selon le statut cible.
     */
    private function createIncident(
        Company $company,
        Solution $solution,
        ?User $technician,
        string $title,
        string $description,
        string $category,
        string $priority,
        string $targetStatus,
        int $daysAgo,
        bool $withEvaluation = false,
        ?int $rating = null,
        ?string $comment = null,
    ): Incident {
        $flow = ['declare', 'analyse', 'pris_en_charge', 'en_traitement', 'resolu', 'cloture'];
        $targetIndex = array_search($targetStatus, $flow);

        $createdAt = Carbon::now()->subDays($daysAgo);

        $incident = Incident::create([
            'code' => $this->nextCode(),
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'priority' => $priority,
            'status' => 'declare',
            'company_id' => $company->id,
            'solution_id' => $solution->id,
            'technician_id' => null,
        ]);
        $incident->created_at = $createdAt;
        $incident->updated_at = $createdAt;
        $incident->save();

        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'old_status' => null,
            'new_status' => 'declare',
            'changed_by' => $company->user_id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $current = 'declare';
        $stepTime = $createdAt->copy();

        for ($i = 1; $i <= $targetIndex; $i++) {
            $next = $flow[$i];
            $stepTime = $stepTime->copy()->addHours(6);

            if ($next === 'pris_en_charge' && $technician) {
                $incident->technician_id = $technician->id;
            }
            if ($next === 'resolu') {
                $incident->resolved_at = $stepTime;
            }
            if ($next === 'cloture') {
                $incident->closed_at = $stepTime;
            }

            $incident->status = $next;
            $incident->updated_at = $stepTime;
            $incident->save();

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'old_status' => $current,
                'new_status' => $next,
                'changed_by' => $technician?->id ?? $company->user_id,
                'created_at' => $stepTime,
                'updated_at' => $stepTime,
            ]);

            if ($next === 'en_traitement' && $technician) {
                Intervention::create([
                    'incident_id' => $incident->id,
                    'technician_id' => $technician->id,
                    'date' => $stepTime->toDateString(),
                    'duration_minutes' => 45,
                    'description' => "Analyse du problème et premières actions correctives sur {$solution->name}.",
                ]);
            }

            $current = $next;
        }

        if ($targetIndex >= 2 && $technician) {
            Message::create([
                'incident_id' => $incident->id,
                'author_id' => $company->user_id,
                'content' => 'Bonjour, avez-vous pu identifier la cause du problème ?',
                'created_at' => $stepTime->copy()->addHour(),
                'updated_at' => $stepTime->copy()->addHour(),
            ]);
            Message::create([
                'incident_id' => $incident->id,
                'author_id' => $technician->id,
                'content' => 'Bonjour, nous avons identifié la cause et travaillons sur la correction.',
                'read_at' => $stepTime->copy()->addHours(2),
                'created_at' => $stepTime->copy()->addHours(2),
                'updated_at' => $stepTime->copy()->addHours(2),
            ]);
        }

        if ($withEvaluation && $rating) {
            Evaluation::create([
                'incident_id' => $incident->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        }

        return $incident;
    }
}
