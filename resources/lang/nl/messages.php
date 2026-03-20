<?php

return [
    'addon_name' => 'Kai Personaliseer',
    'addon_description' => 'Adaptieve contentlevering gebaseerd op bezoekerskenmerken',

    // Dashboard
    'dashboard' => [
        'title' => 'Dashboard',
        'visitors_today' => 'Bezoekers Vandaag',
        'active_sessions' => 'Actieve Sessies',
        'total_visitors' => 'Totaal Bezoekers',
        'unique_visitors' => 'Unieke Bezoekers',
        'page_views' => 'Paginaweergaven',
        'avg_session_duration' => 'Gem. Sessieduur',
        'sessions' => 'Sessies',
        'top_engaged_visitors' => 'Meest Betrokken Bezoekers',
    ],

    // Rules
    'rules' => [
        'title' => 'Regels',
        'create' => 'Regel Aanmaken',
        'edit' => 'Regel Bewerken',
        'delete' => 'Regel Verwijderen',
        'name' => 'Regelnaam',
        'description' => 'Beschrijving',
        'conditions' => 'Voorwaarden',
        'priority' => 'Prioriteit',
        'is_active' => 'Actief',
        'created_at' => 'Aangemaakt',
        'updated_at' => 'Bijgewerkt',
        'no_rules' => 'Geen regels gevonden',
        'limit_reached' => 'Lite limiet bereikt. U kunt maximaal :max actieve regels hebben. Upgrade naar Pro voor onbeperkte regels.',
        'created' => 'Regel succesvol aangemaakt!',
        'updated' => 'Regel succesvol bijgewerkt!',
        'deleted' => 'Regel succesvol verwijderd!',
    ],

    // Visitors
    'visitors' => [
        'title' => 'Bezoekers',
        'fingerprint' => 'Vingerafdruk',
        'first_visit' => 'Eerste Bezoek',
        'last_visit' => 'Laatste Bezoek',
        'visit_count' => 'Aantal Bezoeken',
        'location' => 'Locatie',
        'device' => 'Apparaat',
        'browser' => 'Browser',
        'ip_address' => 'IP-adres',
        'user_agent' => 'User Agent',
        'sessions' => 'Sessies',
        'attributes' => 'Kenmerken',
    ],

    // Segments
    'segments' => [
        'title' => 'Segmenten',
        'create' => 'Segment Aanmaken',
        'edit' => 'Segment Bewerken',
        'name' => 'Segmentnaam',
        'description' => 'Beschrijving',
        'criteria' => 'Criteria',
        'member_count' => 'Leden',
        'pro_feature' => 'Segmenten zijn een Pro functie. Upgrade naar Pro om dynamische bezoekerssegmenten te gebruiken.',
    ],

    // API Connections
    'api_connections' => [
        'title' => 'API-verbindingen',
        'create' => 'Verbinding Aanmaken',
        'edit' => 'Verbinding Bewerken',
        'delete' => 'Verbinding Verwijderen',
        'test' => 'Verbinding Testen',
        'name' => 'Verbindingsnaam',
        'provider' => 'Provider',
        'api_url' => 'API URL',
        'api_key' => 'API Sleutel',
        'auth_type' => 'Authenticatietype',
        'is_active' => 'Actief',
        'cache_duration' => 'Cacheduur (seconden)',
        'timeout' => 'Time-out (seconden)',
        'rate_limit' => 'Snelheidsbeperking (per minuut)',
        'last_used_at' => 'Laatst Gebruikt',
        'test_success' => 'Verbindingstest geslaagd!',
        'test_failed' => 'Verbindingstest mislukt!',
        'limit_reached' => 'Lite limiet bereikt. U kunt maximaal :max actieve API-verbindingen hebben. Upgrade naar Pro voor onbeperkte verbindingen.',
        'created' => 'API-verbinding succesvol aangemaakt!',
        'updated' => 'API-verbinding succesvol bijgewerkt!',
        'deleted' => 'API-verbinding succesvol verwijderd!',
    ],

    // Conditions
    'conditions' => [
        'country' => 'Land',
        'city' => 'Stad',
        'region' => 'Regio',
        'browser' => 'Browser',
        'device_type' => 'Apparaattype',
        'returning_visitor' => 'Terugkerende Bezoeker',
        'time_of_day' => 'Tijd van de Dag',
        'day_of_week' => 'Dag van de Week',
        'weather' => 'Weersomstandigheid',
        'language' => 'Taal',
        'timezone' => 'Tijdzone',
    ],

    // Operators
    'operators' => [
        'equals' => 'Is Gelijk Aan',
        'not_equals' => 'Is Niet Gelijk Aan',
        'contains' => 'Bevat',
        'not_contains' => 'Bevat Niet',
        'greater_than' => 'Groter Dan',
        'less_than' => 'Kleiner Dan',
        'in' => 'In',
        'not_in' => 'Niet In',
    ],

    // Settings
    'settings' => [
        'title' => 'Instellingen',
        'features' => 'Functies',
        'privacy' => 'Privacy',
        'api_keys' => 'API Sleutels',
        'retention' => 'Gegevensbewaring',
        'performance' => 'Prestaties',
        'save' => 'Instellingen Opslaan',
        'saved' => 'Instellingen succesvol opgeslagen!',
    ],

    // Permissions
    'permissions' => [
        'view_dashboard' => 'Dashboard Bekijken',
        'manage_rules' => 'Regels Beheren',
        'view_visitors' => 'Bezoekers Bekijken',
        'manage_segments' => 'Segmenten Beheren',
        'manage_api_connections' => 'API-verbindingen Beheren',
        'manage_settings' => 'Instellingen Beheren',
        'view_analytics' => 'Analytics Bekijken',
    ],

    // General
    'general' => [
        'save' => 'Opslaan',
        'cancel' => 'Annuleren',
        'delete' => 'Verwijderen',
        'edit' => 'Bewerken',
        'create' => 'Aanmaken',
        'view' => 'Bekijken',
        'back' => 'Terug',
        'confirm_delete' => 'Weet u zeker dat u dit wilt verwijderen?',
        'yes' => 'Ja',
        'no' => 'Nee',
    ],

    // Analytics
    'analytics' => [
        'title' => 'Analytics',
        'engagement_score' => 'Betrokkenheidsscore',
        'engagement_score_description' => 'Gebaseerd op bezoekfrequentie, paginaweergaven, tijd doorgebracht en scrolldiepte',
        'behavioral_summary' => 'Gedragsoverzicht',
        'page_history' => 'Paginageschiedenis',
        'max_scroll_depth' => 'Max. Scrolldiepte',
        'reading_time' => 'Leestijd',
        'total_clicks' => 'Totaal Klikken',
        'total_events' => 'Totaal Gebeurtenissen',
        'page' => 'Pagina',
        'collection' => 'Collectie',
        'viewed_at' => 'Bekeken Op',
        'avg_scroll_depth' => 'Gem. Scrolldiepte',
        'avg_reading_time' => 'Gem. Leestijd',
        'pro_feature' => 'Analytics is een Pro functie. Upgrade naar Pro voor paginaniveau analytics en betrokkenheidsscores.',
        'pages' => [
            'title' => 'Pagina Analytics',
            'page' => 'Pagina',
            'views' => 'Weergaven',
            'unique_visitors' => 'Unieke Bezoekers',
            'first_view' => 'Eerste Weergave',
            'last_view' => 'Laatste Weergave',
            'total_views' => 'Totaal Weergaven',
            'avg_scroll_depth' => 'Gem. Scrolldiepte',
            'avg_reading_time' => 'Gem. Leestijd',
            'recent_views' => 'Recente Weergaven',
            'view_details' => 'Details',
        ],
    ],

    // ActiveCampaign
    'activecampaign' => [
        'title' => 'ActiveCampaign',
        'enabled' => 'ActiveCampaign Integratie',
        'api_url' => 'API URL',
        'api_key' => 'API Sleutel',
        'cookie_name' => 'Cookienaam',
        'cache_ttl' => 'Cache TTL (minuten)',
        'test_connection' => 'Verbinding Testen',
        'contact_id' => 'Contact ID',
        'email' => 'E-mail',
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'phone' => 'Telefoon',
        'tags' => 'Labels',
        'lists' => 'Lijsten',
        'custom_fields' => 'Aangepaste Velden',
        'created_at' => 'Account Aangemaakt',
        'updated_at' => 'Laatst Bijgewerkt',
    ],
];
