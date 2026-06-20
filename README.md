# Laravel Vote

> Système de vote polymorphique pour applications Laravel

Un package Laravel complet pour gérer des votes (Positif, Négatif, Abstention, Neutre) avec le pattern Repository, des DTOs, des Value Objects et un système de toggle intelligent.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
  - [Voter](#voter)
  - [Toggle un vote](#toggle-un-vote)
  - [Modifier un vote](#modifier-un-vote)
  - [Supprimer un vote](#supprimer-un-vote)
  - [Vérifier un vote](#vérifier-un-vote)
  - [Compter les votes](#compter-les-votes)
  - [Récupérer les votes](#récupérer-les-votes)
  - [Statistiques](#statistiques)
- [Types de vote](#types-de-vote)
- [Référence de l'API](#référence-de-lapi)
- [Value Objects](#value-objects)
- [Structure de la base de données](#structure-de-la-base-de-données)
- [Tests](#tests)
- [Contribuer](#contribuer)
- [Licence](#licence)

---

## ✨ Fonctionnalités

- ✅ **Double polymorphisme** - Voter sur n'importe quel modèle avec n'importe quel utilisateur
- ✅ **4 types de vote** - Positif, Négatif, Abstention, Neutre
- ✅ **Toggle intelligent** - Changez de vote en un seul appel
- ✅ **Anti-doublon** - Un utilisateur ne peut pas voter deux fois sur le même objet
- ✅ **Support des DTOs** - Objets de transfert de données typés
- ✅ **Value Objects** - DateTime, Métadonnées
- ✅ **Support des métadonnées** - Stockez des données supplémentaires au format JSON
- ✅ **Suppression douce** - Suppression sécurisée avec possibilité de restauration
- ✅ **Filtrage avancé** - Filtrez par type de vote, par auteur, par objet
- ✅ **Statistiques** - Répartition des votes, pourcentages, scores
- ✅ **Tests complets** - Couverture complète des tests d'intégration

---

## 🚀 Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.0, 13.0, 14.0 ou 15.0

---

## 📦 Installation

Installez le package via Composer :

```bash
composer require andydefer/laravel-vote
```

### Publier les migrations

```bash
php artisan vendor:publish --tag=vote-migrations
```

### Exécuter les migrations

```bash
php artisan migrate
```

---

## ⚙️ Configuration

Le package est automatiquement découvert par Laravel. Aucune configuration supplémentaire n'est requise.

Si vous devez personnaliser le Service Provider, ajoutez-le manuellement dans `config/app.php` :

```php
'providers' => [
    // ...
    AndyDefer\LaravelVote\VoteServiceProvider::class,
],
```

---

## 📖 Utilisation

### Voter

```php
use AndyDefer\LaravelVote\Services\VoteService;
use AndyDefer\LaravelVote\Enums\VoteType;

class CommentController extends Controller
{
    public function vote(VoteService $voteService, Comment $comment)
    {
        $user = auth()->user();

        // Vote positif (👍)
        $vote = $voteService->vote(
            voter: $user,
            votable: $comment,
            type: VoteType::POSITIVE
        );

        // Vote négatif (👎)
        $vote = $voteService->vote(
            voter: $user,
            votable: $comment,
            type: VoteType::NEGATIVE
        );

        // Vote abstention (🤷)
        $vote = $voteService->vote(
            voter: $user,
            votable: $comment,
            type: VoteType::ABSTENTION
        );

        // Vote neutre (😐)
        $vote = $voteService->vote(
            voter: $user,
            votable: $comment,
            type: VoteType::NEUTRAL
        );

        return response()->json([
            'message' => 'Vote enregistré',
            'type' => $vote->getType()->value,
            'emoji' => $vote->getType()->getEmoji()
        ]);
    }
}
```

### Toggle un vote

La méthode `toggle()` permet de :
- Ajouter un vote s'il n'existe pas
- Supprimer le vote si le même type est utilisé
- Changer de type de vote s'il existe déjà

```php
public function toggleVote(VoteService $voteService, Comment $comment)
{
    $user = auth()->user();

    // Toggle un vote positif (ajoute ou supprime)
    $voted = $voteService->toggle($user, $comment, VoteType::POSITIVE);

    // Toggle un vote négatif (ajoute ou supprime)
    $voted = $voteService->toggle($user, $comment, VoteType::NEGATIVE);

    // Changer de type de vote
    // Si l'utilisateur a voté positif, cela changera en négatif
    // Si l'utilisateur a voté négatif, cela changera en positif
    $voted = $voteService->toggle($user, $comment, VoteType::NEGATIVE);

    return response()->json([
        'voted' => $voted,
        'type' => $voted ? VoteType::POSITIVE->value : null,
        'emoji' => $voted ? VoteType::POSITIVE->getEmoji() : null,
    ]);
}
```

### Modifier un vote

```php
public function updateVote(VoteService $voteService, Comment $comment)
{
    $user = auth()->user();

    try {
        $updated = $voteService->updateVote(
            voter: $user,
            votable: $comment,
            type: VoteType::NEUTRAL
        );

        return response()->json([
            'message' => 'Vote mis à jour',
            'vote' => $updated
        ]);
    } catch (RuntimeException $e) {
        return response()->json(['error' => $e->getMessage()], 404);
    }
}
```

### Supprimer un vote

```php
public function deleteVote(VoteService $voteService, Comment $comment)
{
    $user = auth()->user();

    try {
        $voteService->deleteVote($user, $comment);

        return response()->json([
            'message' => 'Vote supprimé avec succès'
        ]);
    } catch (RuntimeException $e) {
        return response()->json(['error' => $e->getMessage()], 404);
    }
}
```

### Vérifier un vote

```php
// Vérifier si l'utilisateur a voté
$hasVoted = $voteService->hasVoted($user, $comment);

// Vérifier le type de vote de l'utilisateur
$hasPositive = $voteService->hasVotedType($user, $comment, VoteType::POSITIVE);

// Récupérer le vote spécifique
$vote = $voteService->getVoterVote($user, $comment);

// Obtenir le type de vote de l'utilisateur
$type = $voteService->getVoterVoteType($user, $comment);
```

### Compter les votes

```php
// Compter tous les votes
$total = $voteService->countVotes($comment);

// Compter les votes par type
$positive = $voteService->countVotesByType($comment, VoteType::POSITIVE);
$negative = $voteService->countVotesByType($comment, VoteType::NEGATIVE);
$abstentions = $voteService->countVotesByType($comment, VoteType::ABSTENTION);
$neutrals = $voteService->countVotesByType($comment, VoteType::NEUTRAL);
```

### Récupérer les votes

```php
// Récupérer tous les voteurs d'un objet
$voters = $voteService->getVoters($comment);

// Récupérer les voteurs par type
$positiveVoters = $voteService->getVotersByType($comment, VoteType::POSITIVE);

// Récupérer tous les votes d'un utilisateur
$userVotes = $voteService->getVoterVotes($user);

// Récupérer les votes d'un utilisateur par type
$userPositiveVotes = $voteService->getVoterVotesByType($user, VoteType::POSITIVE);
```

### Statistiques

```php
// Score total (positif - négatif)
$score = $voteService->getScore($comment); // 42

// Taux de participation
$participation = $voteService->getParticipationRate($comment); // 75.5%

// Répartition des votes
$distribution = $voteService->getDistribution($comment);
// [
//     'positive' => 42,
//     'negative' => 20,
//     'abstention' => 10,
//     'neutral' => 8,
//     'total' => 80
// ]

// Pourcentage par type
$positivePercentage = $voteService->getPercentage($comment, VoteType::POSITIVE); // 52.5%

// Statistiques complètes
$stats = $voteService->getStats($comment);
// [
//     'positive' => 42,
//     'negative' => 20,
//     'abstention' => 10,
//     'neutral' => 8,
//     'total' => 80,
//     'score' => 22,
//     'participation_rate' => 75.5,
//     'distribution' => [
//         'positive' => 52.5,
//         'negative' => 25.0,
//         'abstention' => 12.5,
//         'neutral' => 10.0
//     ]
// ]
```

---

## 🏷️ Types de vote

| Type | Valeur | Emoji | Label | Description |
|------|--------|-------|-------|-------------|
| `VoteType::POSITIVE` | `'positive'` | 👍 | Positif | Vote favorable |
| `VoteType::NEGATIVE` | `'negative'` | 👎 | Négatif | Vote défavorable |
| `VoteType::ABSTENTION` | `'abstention'` | 🤷 | Abstention | Vote neutre sans avis |
| `VoteType::NEUTRAL` | `'neutral'` | 😐 | Neutre | Vote sans opinion |

### Utilisation des émojis et labels

```php
use AndyDefer\LaravelVote\Enums\VoteType;

$type = VoteType::POSITIVE;
echo $type->getEmoji();  // 👍
echo $type->getLabel();  // Positif
echo $type->getColor();  // green

$type = VoteType::NEGATIVE;
echo $type->getEmoji();  // 👎
echo $type->getLabel();  // Négatif
echo $type->getColor();  // red

$type = VoteType::ABSTENTION;
echo $type->getEmoji();  // 🤷
echo $type->getLabel();  // Abstention
echo $type->getColor();  // gray

$type = VoteType::NEUTRAL;
echo $type->getEmoji();  // 😐
echo $type->getLabel();  // Neutre
echo $type->getColor();  // blue
```

---

## 📚 Référence de l'API

### VoteService

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `vote(Model $voter, Model $votable, VoteType $type)` | Crée un vote | `Model` |
| `toggle(Model $voter, Model $votable, VoteType $type)` | Toggle un vote (ajoute/change/supprime) | `bool` |
| `updateVote(Model $voter, Model $votable, VoteType $type)` | Modifie un vote existant | `Model` |
| `deleteVote(Model $voter, Model $votable)` | Supprime un vote | `void` |
| `hasVoted(Model $voter, Model $votable)` | Vérifie si l'utilisateur a voté | `bool` |
| `hasVotedType(Model $voter, Model $votable, VoteType $type)` | Vérifie si l'utilisateur a voté pour un type | `bool` |
| `getVoterVote(Model $voter, Model $votable)` | Récupère le vote d'un utilisateur | `?Model` |
| `getVoterVoteType(Model $voter, Model $votable)` | Récupère le type de vote d'un utilisateur | `?VoteType` |
| `getVotes(Model $votable)` | Récupère tous les votes d'un objet | `Collection` |
| `getVoterVotes(Model $voter)` | Récupère les votes d'un utilisateur | `Collection` |
| `getVoterVotesByType(Model $voter, VoteType $type)` | Récupère les votes d'un utilisateur par type | `Collection` |
| `getVoters(Model $votable)` | Récupère tous les voteurs | `Collection` |
| `getVotersByType(Model $votable, VoteType $type)` | Récupère les voteurs par type | `Collection` |
| `countVotes(Model $votable)` | Compte tous les votes | `int` |
| `countVotesByType(Model $votable, VoteType $type)` | Compte les votes par type | `int` |
| `getScore(Model $votable)` | Score total (positif - négatif) | `int` |
| `getParticipationRate(Model $votable)` | Taux de participation | `float` |
| `getDistribution(Model $votable)` | Répartition des votes | `array` |
| `getPercentage(Model $votable, VoteType $type)` | Pourcentage de votes d'un type | `float` |
| `getStats(Model $votable)` | Statistiques complètes | `array` |

---

## 🎯 Value Objects

Le package supporte les Value Objects suivants :

| Value Object | Description | Exemple |
|--------------|-------------|---------|
| `DateTimeVO` | Date/heure | `DateTimeVO::from('2024-01-01 12:00:00')` |
| `StrictDataObject` | Métadonnées typées | `StrictDataObject::from(['key' => 'value'])` |

### Accesseurs dans le modèle Vote

```php
$vote = Vote::find(1);

// Accès via les getters
$createdAt = $vote->getCreatedAt();    // DateTimeVO|null
$updatedAt = $vote->getUpdatedAt();    // DateTimeVO|null
$deletedAt = $vote->getDeletedAt();    // DateTimeVO|null
$metadata = $vote->getMetadata();      // StrictDataObject|null
$type = $vote->getType();              // VoteType

// Relations
$voter = $vote->voter;          // Auteur (User, Admin, etc.)
$votable = $vote->votable;      // Objet voté (Comment, Article, etc.)
```

---

## 📝 Structure de la base de données

```sql
CREATE TABLE votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voter_type VARCHAR(255) NOT NULL,      -- Type de l'électeur
    voter_id BIGINT UNSIGNED NOT NULL,     -- ID de l'électeur
    votable_type VARCHAR(255) NOT NULL,    -- Type de l'objet voté
    votable_id BIGINT UNSIGNED NOT NULL,   -- ID de l'objet voté
    type VARCHAR(20) NOT NULL,             -- positive, negative, abstention, neutral
    metadata JSON NULL,                    -- Métadonnées
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE INDEX idx_unique_vote (voter_type, voter_id, votable_type, votable_id),
    INDEX idx_voter (voter_type, voter_id),
    INDEX idx_votable (votable_type, votable_id),
    INDEX idx_type (type)
);
```

---

## 🔍 Exemple complet

```php
use AndyDefer\LaravelVote\Services\VoteService;
use AndyDefer\LaravelVote\Enums\VoteType;
use Illuminate\Http\Request;

class ProposalVoteController extends Controller
{
    public function __construct(
        private readonly VoteService $voteService
    ) {}

    public function store(Request $request, Proposal $proposal)
    {
        $user = $request->user();

        $type = VoteType::tryFrom($request->input('type'));
        if (!$type) {
            return response()->json([
                'error' => 'Type de vote invalide'
            ], 400);
        }

        try {
            $vote = $this->voteService->vote(
                voter: $user,
                votable: $proposal,
                type: $type
            );

            return response()->json([
                'message' => 'Vote enregistré avec succès',
                'vote' => [
                    'type' => $vote->getType()->value,
                    'emoji' => $vote->getType()->getEmoji(),
                    'label' => $vote->getType()->getLabel(),
                ],
                'stats' => $this->voteService->getStats($proposal)
            ], 201);

        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function toggle(Request $request, Proposal $proposal)
    {
        $user = $request->user();
        $type = VoteType::tryFrom($request->input('type', 'positive'));

        if (!$type) {
            return response()->json([
                'error' => 'Type de vote invalide'
            ], 400);
        }

        $voted = $this->voteService->toggle($user, $proposal, $type);

        return response()->json([
            'voted' => $voted,
            'type' => $voted ? $type->value : null,
            'emoji' => $voted ? $type->getEmoji() : null,
            'stats' => $this->voteService->getStats($proposal)
        ]);
    }

    public function show(Proposal $proposal)
    {
        $stats = $this->voteService->getStats($proposal);
        $userVote = null;

        if (auth()->check()) {
            $userVote = $this->voteService->getVoterVoteType(auth()->user(), $proposal);
        }

        return response()->json([
            'proposal' => $proposal->id,
            'title' => $proposal->title,
            'stats' => $stats,
            'user_vote' => $userVote?->value,
            'user_emoji' => $userVote?->getEmoji(),
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        $user = request()->user();

        try {
            $this->voteService->deleteVote($user, $proposal);

            return response()->json([
                'message' => 'Vote supprimé avec succès',
                'stats' => $this->voteService->getStats($proposal)
            ]);

        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function myVotes(Request $request)
    {
        $user = $request->user();
        $votes = $this->voteService->getVoterVotes($user);

        return response()->json([
            'total' => $votes->count(),
            'votes' => $votes->map(function ($vote) {
                return [
                    'id' => $vote->id,
                    'type' => $vote->getType()->value,
                    'emoji' => $vote->getType()->getEmoji(),
                    'votable_type' => $vote->votable_type,
                    'votable_id' => $vote->votable_id,
                    'created_at' => $vote->getCreatedAt()?->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    public function stats(Proposal $proposal)
    {
        $stats = $this->voteService->getStats($proposal);
        $score = $this->voteService->getScore($proposal);

        return response()->json([
            'proposal' => $proposal->id,
            'score' => $score,
            'participation_rate' => $stats['participation_rate'],
            'positive' => $stats['positive'],
            'negative' => $stats['negative'],
            'abstention' => $stats['abstention'],
            'neutral' => $stats['neutral'],
            'total' => $stats['total'],
            'distribution' => $stats['distribution']
        ]);
    }

    public function leaderboard()
    {
        $proposals = Proposal::withCount([
            'votes as positive_votes' => function ($query) {
                $query->where('type', 'positive');
            },
            'votes as negative_votes' => function ($query) {
                $query->where('type', 'negative');
            }
        ])->having('positive_votes', '>', 0)
          ->orderByRaw('positive_votes - negative_votes DESC')
          ->limit(10)
          ->get();

        return response()->json($proposals->map(function ($proposal) {
            return [
                'id' => $proposal->id,
                'title' => $proposal->title,
                'score' => $proposal->positive_votes - $proposal->negative_votes,
                'positive_votes' => $proposal->positive_votes,
                'negative_votes' => $proposal->negative_votes
            ];
        }));
    }
}
```

---

## 🚀 Cas d'utilisation

### 1. Système de votes pour propositions

```php
// Afficher les propositions avec leurs votes
public function getProposals()
{
    $proposals = Proposal::with('votes')->get();

    return response()->json($proposals->map(function ($proposal) {
        $stats = $this->voteService->getStats($proposal);

        return [
            'id' => $proposal->id,
            'title' => $proposal->title,
            'score' => $stats['score'],
            'positive' => $stats['positive'],
            'negative' => $stats['negative'],
            'participation' => $stats['participation_rate']
        ];
    }));
}
```

### 2. Système de feedback

```php
// Feedback sur une fonctionnalité
public function feedback(Feature $feature)
{
    $user = auth()->user();
    
    $this->voteService->vote($user, $feature, VoteType::POSITIVE);
    
    // Si assez de votes positifs, prioriser la fonctionnalité
    $stats = $this->voteService->getStats($feature);
    if ($stats['score'] > 50) {
        $feature->update(['priority' => 'high']);
    }
    
    return response()->json(['message' => 'Feedback enregistré']);
}
```

### 3. Système d'approbation

```php
// Approbation d'un document
public function approve(Document $document)
{
    $user = auth()->user();
    
    $this->voteService->vote($user, $document, VoteType::POSITIVE);
    
    // Si suffisamment d'approbations, valider le document
    $stats = $this->voteService->getStats($document);
    if ($stats['positive'] >= 5 && $stats['positive'] > $stats['negative'] * 2) {
        $document->update(['status' => 'approved']);
    }
    
    return response()->json(['message' => 'Document approuvé']);
}
```

---

## 🧪 Tests

### Exécuter les tests

```bash
composer test
```

### Exécuter uniquement les tests unitaires

```bash
composer test-unit
```

### Exécuter uniquement les tests d'intégration

```bash
composer test-integration
```

---

## 🔧 Développement

### Style de code

```bash
./vendor/bin/pint
```

### Analyse statique

```bash
./vendor/bin/phpstan analyse
./vendor/bin/psalm
```

---

## 🤝 Contribuer

Veuillez consulter [CONTRIBUTING](CONTRIBUTING.md) pour plus de détails.

### Flux de développement

1. Forkez le dépôt
2. Créez une branche de fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Apportez vos modifications
4. Exécutez les tests (`composer test`)
5. Committez vos modifications (`git commit -m 'Ajouter une fonctionnalité géniale'`)
6. Poussez vers la branche (`git push origin feature/amazing-feature`)
7. Ouvrez une Pull Request

---

## 📦 Dépendances

- [`andydefer/php-vo`](https://github.com/andydefer/php-vo) - Value Objects
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Implémentation du pattern Repository
- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine (AbstractRecord, AbstractData)

---

## 👨‍💻 Auteur

**Andy Kani**
- GitHub: [@andydefer](https://github.com/andydefer)
- Email: andykanidimbu@gmail.com

---

## 📄 Licence

Ce package est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus d'informations.

---

## ⭐ Support

Si vous trouvez ce package utile, n'hésitez pas à lui donner une ⭐ sur GitHub !

---

## 🙏 Remerciements

- Framework Laravel
- Tous les contributeurs et utilisateurs de ce package

---

**Construit avec ❤️ pour la communauté Laravel**