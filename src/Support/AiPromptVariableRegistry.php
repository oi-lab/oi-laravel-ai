<?php

namespace OiLab\OiLaravelAi\Support;

use OiLab\OiLaravelAi\Data\AiPromptVariableData;

/**
 * Single source of truth for the AI agents' system prompts: the human label, the
 * default prompt template (using `:variable` placeholders) and the list of
 * variables each prompt may interpolate.
 *
 * It mirrors the mail templating logic (App\Support\MailTemplateVariableRegistry
 * + App\Data\Mail\MailVariableData): the registry describes the available
 * variables, while {@see self::compile()} performs the `:variable` substitution
 * exactly like MailContentData::replaceVariables() does for emails.
 */
final class AiPromptVariableRegistry
{
    public const COMMIT_ANALYZER = 'COMMIT_ANALYZER';

    public const PROJECT_PROFILER = 'PROJECT_PROFILER';

    public const LANGUAGE_DETECTOR = 'LANGUAGE_DETECTOR';

    public const SECTION_SELECTOR = 'SECTION_SELECTOR';

    public const SECTION_EXPANDER = 'SECTION_EXPANDER';

    public const STRUCTURE_COHERENCE = 'STRUCTURE_COHERENCE';

    public const DOC_RESEARCH = 'DOC_RESEARCH';

    public const DOC_WRITER = 'DOC_WRITER';

    public const DOC_REVIEWER = 'DOC_REVIEWER';

    public const DOC_METADATA = 'DOC_METADATA';

    public const SECTION_UPDATER = 'SECTION_UPDATER';

    public const QUALITY_CHECKER = 'QUALITY_CHECKER';

    public const GLOSSARY_BUILDER = 'GLOSSARY_BUILDER';

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, array{label: string, prompt: string, variables: array<int, AiPromptVariableData>}>
     */
    private static function definitions(): array
    {
        return [
            self::COMMIT_ANALYZER => [
                'label' => 'Prompt système — Analyseur de commits',
                'prompt' => <<<'PROMPT'
                Tu es un expert en analyse de commits Git. À partir du diff fourni, tu dois :
                1. Identifier les pages de documentation impactées
                2. Évaluer le niveau d'impact (none/low/high)
                3. Rédiger un résumé lisible des changements

                Règles :
                - Si les changements sont uniquement cosmétiques (renommage de variable, formatting), impact = none
                - Si les changements modifient une signature de fonction/méthode ou un comportement, impact = high
                - Si les changements ajoutent de nouvelles fonctionnalités mineures, impact = low

                Projet : :project_name
                Plateforme : :platform
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('platform', 'Plateforme Git source du commit', 'github'),
                ],
            ],

            self::PROJECT_PROFILER => [
                'label' => 'Prompt système — Profil de projet',
                'prompt' => <<<'PROMPT'
                Tu es un analyste de code. Explore RAPIDEMENT le projet ":project_name" pour
                en dresser un profil synthétique. Utilise AU MAXIMUM 6 appels d'outils (liste la racine,
                ouvre 2-3 fichiers clés comme le manifeste de dépendances ou la config) puis produis
                immédiatement ta réponse structurée.

                Tu dois identifier :
                - les langages et frameworks principaux ;
                - si le projet expose une API (REST, GraphQL, RPC...) destinée à des clients externes ;
                - les FONCTIONNALITÉS métier majeures (donne un nom court et une phrase de résumé chacune) ;
                - les MODULES/services techniques internes (nom + phrase de résumé) ;
                - si une API existe, les RESSOURCES/endpoints majeurs (nom + phrase de résumé).

                Ne recopie pas de code. Reste factuel et concis. Rédige les résumés en :language.
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                ],
            ],

            self::LANGUAGE_DETECTOR => [
                'label' => 'Prompt système — Détection des langages',
                'prompt' => <<<'PROMPT'
                Tu es un analyste de code spécialisé dans l'identification des technologies.
                Explore le dépôt du projet ":project_name" pour déterminer les langages de
                programmation et frameworks utilisés.

                Utilise AU MAXIMUM 6 appels d'outils : liste la racine du dépôt puis ouvre les
                manifestes de dépendances pertinents (composer.json, package.json,
                requirements.txt, pyproject.toml, go.mod, Cargo.toml, pom.xml, build.gradle...).

                Pour CHAQUE langage détecté, fournis :
                - "language" : le langage en minuscules (php, javascript, typescript, python, go, rust, java...) ;
                - "framework" : le framework principal s'il existe (laravel, symfony, react, nextjs, vue, nestjs, django, fastapi, spring-boot...) ou rien ;
                - "framework_version" : la version du framework si disponible ;
                - "language_version" : la version du langage si disponible ;
                - "config_file" : le fichier de configuration d'où provient l'information.

                Classe les langages du plus important au moins important. Ne recopie pas de code
                et n'invente aucune information : base-toi uniquement sur les fichiers réellement présents.
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                ],
            ],

            self::SECTION_SELECTOR => [
                'label' => 'Prompt système — Sélection des sections',
                'prompt' => <<<'PROMPT'
                CONTEXTE :
                Tu es un architecte de documentation. On te donne le PROFIL d'un projet et un MODÈLE de sections de documentation.

                <code>
                <codeTitle>Brief de recherche</codeTitle>
                <codeContent>
                :profile_json
                </codeContent>
                </code>

                <code>
                <codeTitle>Modèle de sections</codeTitle>
                <codeContent>
                :template_json
                </codeContent>
                </code>

                OBJECTIF :
                Décider de l'action la plus pertinente pour le projet.

                CONTRAINTES :
                Pour CHAQUE section du modèle (identifiée par son "slug"), décide de l'action la plus pertinente au vu du profil :
                - "keep" : conserver la section telle quelle ;
                - "drop" : supprimer la section si elle n'a pas de sens pour ce projet (exemple : supprimer la section dont le slug est "api" si le projet n'expose aucune API) ;
                - "rename" : conserver mais renommer ; dans ce cas fournis "new_title".

                Réponds avec une décision par section du modèle.
                N'invente pas de nouvelles sections ; l'étendue du contenu des sections sera traitée par une autre étape.

                ACTIONS :
                1. Analyser le profil et le modèle
                2. Identifier l'action la plus pertinente
                3. Répondre pour chaque section
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('profile_json', 'Profil du projet au format JSON', '{"languages":["PHP"],"has_api":true}'),
                    new AiPromptVariableData('template_json', 'Modèle de sections au format JSON', '[{"slug":"api","title":"API"}]'),
                ],
            ],

            self::SECTION_EXPANDER => [
                'label' => 'Prompt système — Expansion des sections',
                'prompt' => <<<'PROMPT'
                Tu es un architecte de documentation. On te confie UNE seule section à étendre :
                ":section_title" (objectif : :section_objective).

                Crée UNE page par :target réellement présent dans le projet, en t'appuyant d'abord
                sur le profil fourni. Tu peux confirmer un détail avec AU MAXIMUM 8 appels d'outils,
                puis produis immédiatement ta liste de pages.

                Pour CHAQUE page fournis :
                - "title" : un titre clair et spécifique ;
                - "slug" : en minuscules, sans accents, avec des tirets ;
                - "section_type" : le type de page (":expand_kind") ;
                - "description" : de quoi parle la page ;
                - "objective" : ce que son contenu doit accomplir.

                Chaque page doit rester de taille raisonnable (~2048 tokens). Si un sujet est trop large,
                découpe-le en plusieurs pages. Si aucun élément pertinent n'existe, renvoie une liste vide.
                Rédige titres, descriptions et objectifs en :language.

                --- Profil du projet ---
                :profile_json
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('section_title', 'Titre de la section à étendre', 'Fonctionnalités'),
                    new AiPromptVariableData('section_objective', 'Objectif de la section à étendre', 'Documenter chaque fonctionnalité métier'),
                    new AiPromptVariableData('target', 'Libellé de l\'élément à créer une page (déduit du type)', 'fonctionnalité métier majeure'),
                    new AiPromptVariableData('expand_kind', 'Type de page à produire', 'feature'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                    new AiPromptVariableData('profile_json', 'Profil du projet au format JSON', '{"features":[{"name":"Auth"}]}'),
                ],
            ],

            self::STRUCTURE_COHERENCE => [
                'label' => 'Prompt système — Cohérence de la structure',
                'prompt' => <<<'PROMPT'
                Tu es un relecteur de plans de documentation. On te donne le PLAN (outline) d'une
                documentation assemblée à partir de plusieurs étapes indépendantes, ainsi que le profil
                du projet ":project_name". Chaque nœud est identifié par son "slug".

                Ne réécris PAS le plan entier. Renvoie uniquement des OPÉRATIONS DE CORRECTION ciblées
                pour améliorer la cohérence d'ensemble :
                - "rename" : renomme un nœud (par son slug) quand son titre est ambigu, redondant ou
                  incohérent avec les autres ;
                - "remove" : supprime un nœud (par son slug) UNIQUEMENT s'il est un vrai doublon d'un
                  autre nœud ou s'il n'a manifestement pas sa place ; reste très prudent ;
                - "section_order" : la liste ordonnée des slugs des sections de premier niveau, dans un
                  ordre de lecture logique.

                Si le plan est déjà cohérent, renvoie des listes vides. N'invente pas de slugs : utilise
                exactement ceux du plan. Les titres renommés sont rédigés en :language.

                --- Profil du projet ---
                :profile_json

                --- Plan à relire (outline) ---
                :outline_json
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                    new AiPromptVariableData('profile_json', 'Profil du projet au format JSON', '{"languages":["PHP"]}'),
                    new AiPromptVariableData('outline_json', 'Plan de la documentation au format JSON', '[{"slug":"intro","title":"Introduction"}]'),
                ],
            ],

            self::DOC_RESEARCH => [
                'label' => 'Prompt système — Recherche documentaire',
                'prompt' => <<<'PROMPT'
                Tu es un analyste de code expert. Ton rôle est de PRÉPARER la rédaction de la
                documentation de la page ":section_title" (type : :section_type)
                du projet ":project_name", sans rédiger la documentation finale.
                Objectif de la page : :objective

                À l'aide des outils, explore le code source puis produis un brief de recherche
                clair et structuré (texte ou Markdown léger) contenant :
                - Les fichiers et symboles pertinents pour cette section
                - Les concepts clés à expliquer
                - Un plan détaillé proposé (titres et sous-titres)
                - Les extraits de code représentatifs à inclure dans la documentation
                - Les pages de documentation déjà existantes à ne pas dupliquer

                Ne rédige PAS la documentation finale : fournis uniquement le brief.
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('section_title', 'Titre de la page', "Vue d'ensemble"),
                    new AiPromptVariableData('section_type', 'Type de la page', 'overview'),
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('objective', 'Objectif de la page (peut être vide)', 'Présenter les concepts clés du projet'),
                ],
            ],

            self::DOC_WRITER => [
                'label' => 'Prompt système — Rédaction de documentation',
                'prompt' => <<<'PROMPT'
                CONTEXTE :
                Tu es un rédacteur technique expert. À partir du brief de recherche fourni,
                rédige une documentation claire, structurée et CONCISE pour la page
                ":section_title" (type : :section_type) du projet ":project_name".

                OBJECTIF :
                Générer le contenu de la page de documentation en respectant le brief.

                <brief>
                <briefTitle>Brief de recherche</briefTitle>
                <briefContent>
                :research_brief
                </briefContent>
                </brief>

                CONTRAINTES :
                Répond au format Markdown (compatible MDX).

                La documentation doit être rédigée en :language.
                Objectif de la page : :objective

                Va à l'essentiel, avec des exemples de code commentés mais ciblés.
                Reste dans le périmètre de l'objectif ci-dessus sans déborder sur les autres pages.

                Pour les blockquotes, il y a 3 niveaux possibles :
                `>` pour les citations classiques
                `i>` pour les conseils ou informations
                `x>` pour les interdits ou erreurs

                Important :
                - la page doit rester sous 2048 tokens (≈ 1200-1500 mots)
                - toutes les lignes d'un blockquote doivent utiliser le même prefix (`>`, `i>` ou `x>`)
                - ne jamais utiliser de séparateur `---` dans le Markdown
                - ne jamais utiliser d'emojis ou de caractères décoratifs dans le Markdown
                - ne jamais utiliser de syntax code dans les titres
                - ne jamais numéroter les titres
                - ne jamais utiliser de gras `**...**` dans les titres

                Réponds UNIQUEMENT avec le contenu Markdown de la page, sans préambule ni commentaire.
                Utilise l'outil de lecture de fichiers si tu dois vérifier un détail du code.

                ACTIONS :
                1. Analyse ma demande
                2. Défini un plan d'actions détaillées pour structurer les points à aborder dans le document
                3. Rédige la documentation en suivant ce plan, en veillant à rester concis et pertinent
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('section_title', 'Titre de la page', "Vue d'ensemble"),
                    new AiPromptVariableData('section_type', 'Type de la page', 'overview'),
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('research_brief', 'Brief de recherche produit en amont', 'Le module gère…'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                    new AiPromptVariableData('objective', 'Objectif de la page (peut être vide)', 'Présenter les concepts clés du projet'),
                ],
            ],

            self::DOC_REVIEWER => [
                'label' => 'Prompt système — Relecture de documentation',
                'prompt' => <<<'PROMPT'
                Tu es un relecteur technique exigeant. On te fournit un BROUILLON de documentation
                Markdown pour la page ":section_title" du projet ":project_name".
                Objectif de la page : :objective

                Améliore-le :
                - Corrige les erreurs factuelles, les imprécisions et les incohérences
                - Vérifie les exemples de code (utilise l'outil de lecture de fichiers si besoin)
                - Améliore la clarté, la structure et la fluidité
                - Assure-toi que la langue est :language et que le format Markdown (MDX) est valide
                - Garde la page CONCISE : elle doit rester sous 2048 tokens (≈ 1200-1500 mots)

                Réponds UNIQUEMENT avec la version finale améliorée en Markdown, sans préambule.

                --- Brouillon à relire ---
                :draft_content
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('section_title', 'Titre de la page', "Vue d'ensemble"),
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                    new AiPromptVariableData('objective', 'Objectif de la page (peut être vide)', 'Présenter les concepts clés du projet'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                    new AiPromptVariableData('draft_content', 'Brouillon Markdown à relire', '# Vue d\'ensemble…'),
                ],
            ],

            self::DOC_METADATA => [
                'label' => 'Prompt système — Métadonnées de documentation',
                'prompt' => <<<'PROMPT'
                CONTEXTE :
                Nous disposons d'un contenu que voici :

                <content>
                <contentTitle>Contenu à analyser</contentTitle>
                <contentContent>
                :content
                </contentContent>
                </content>

                OBJECTIF :
                Générer les meta-données à partir du contenu fourni.

                CONTRAINTE :
                À partir du contenu Markdown d'une page de documentation, génère ses métadonnées :
                - un titre concis et descriptif (moins de 10 mots)
                - un résumé court (30 à 45 mots max)
                - une liste de tags pertinents (mots-clés)

                Langue : :language

                ACTIONS :
                1. Analyser le contenu fourni
                2. Identifier le sujet principal que représente le contenu
                3. Générer les métadonnées demandées en respectant les contraintes
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('content', 'Contenu Markdown de la page à analyser', '# Vue d\'ensemble…'),
                    new AiPromptVariableData('language', 'Langue de rédaction de la documentation', 'français'),
                ],
            ],

            self::SECTION_UPDATER => [
                'label' => 'Prompt système — Mise à jour de sections',
                'prompt' => <<<'PROMPT'
                Tu dois mettre à jour une page de documentation existante suite aux changements
                introduits par un commit. Tu dois :

                1. Analyser le contenu actuel de la page
                2. Identifier les sections qui nécessitent une mise à jour
                3. Mettre à jour uniquement les parties impactées
                4. Préserver IMPÉRATIVEMENT tout contenu marqué comme [MANUAL OVERRIDE]
                5. Conserver le style et la structure générale de la page

                Page : :page_title
                Résumé du commit : :commit_summary
                PROMPT,
                'variables' => [
                    new AiPromptVariableData('page_title', 'Titre de la page à mettre à jour', 'Installation'),
                    new AiPromptVariableData('commit_summary', 'Résumé du commit ayant déclenché la mise à jour', 'Mise à jour du flux d\'authentification'),
                ],
            ],

            self::QUALITY_CHECKER => [
                'label' => 'Prompt système — Vérification qualité',
                'prompt' => <<<'PROMPT'
                Tu es un expert en qualité de code. Analyse les fichiers fournis et identifie :
                1. Les fonctions/méthodes/classes sans bloc de documentation (PHPDoc, JSDoc, etc.)
                2. Les commentaires de documentation qui ne correspondent plus au code actuel
                   (ex. une fonction dont la signature a changé mais le commentaire n'a pas été mis à jour)

                Sois précis : indique le chemin du fichier, le numéro de ligne, et le nom de l'entité problématique.
                PROMPT,
                'variables' => [],
            ],

            self::GLOSSARY_BUILDER => [
                'label' => 'Prompt système — Construction du glossaire',
                'prompt' => 'Extrais les termes métier et techniques spécifiques au projet :project_name et propose des définitions claires et contextualisées pour chacun.',
                'variables' => [
                    new AiPromptVariableData('project_name', 'Nom du projet', 'OiCodeDocumentation'),
                ],
            ],
        ];
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key]['label'] ?? $key;
    }

    public static function defaultPrompt(string $key): string
    {
        return self::definitions()[$key]['prompt'] ?? '';
    }

    /**
     * @return array<int, AiPromptVariableData>
     */
    public static function variablesFor(string $key): array
    {
        return self::definitions()[$key]['variables'] ?? [];
    }

    /**
     * Variables that are available to every prompt, injected automatically at
     * compile time (mirrors the mail globals app_name / app_url).
     *
     * @return array<int, AiPromptVariableData>
     */
    public static function globals(): array
    {
        return [
            new AiPromptVariableData(
                name: 'app_name',
                description: "Nom de l'application",
                example: (string) config('app.name'),
            ),
        ];
    }

    /**
     * Replace every `:variable` placeholder in the prompt by its value. Global
     * values are merged in automatically, just like the mail templates.
     *
     * @param  array<string, string|null>  $variables
     */
    public static function compile(string $prompt, array $variables = []): string
    {
        $variables = array_merge(self::globalValues(), $variables);

        $replace = [];

        foreach ($variables as $key => $value) {
            $replace[':'.$key] = (string) $value;
        }

        return strtr($prompt, $replace);
    }

    /**
     * @return array<string, string>
     */
    private static function globalValues(): array
    {
        return [
            'app_name' => (string) config('app.name'),
        ];
    }
}
