<?php

return [
  /**
   *
   * /// ---------------------------------------------------------------------------------------------------------
   * /// Reusable sections
   * ///
   * /// These sections can be included and will be merged into a mail
   * /// ---------------------------------------------------------------------------------------------------------
   *
   */
  'includes' => [
    'customer' => [
      '_group_header' => 'Kundeinformasjon',
      'firstname' => 'Fornavn',
      'surname' => 'Etternavn',
      'mail' => 'E-post addresse'
    ],
    'order' => [
      'data' => [
        'order' => [
          '_group_header' => "Ordre",
          'secret' => 'Ordrens hemmelige kode, ofte brukt i URLer som kvitteringsurler',
          'checkout' => [
            'firstname' => 'Kundens fornavn',
            'surname' => 'Kundens etternavn'
          ]
        ],
      ],
    ],
    'article' => [
      'article' => [
        '_group_header' => 'Artikkelinformasjon',
        'name' => 'Navn på artikkel',
        'author' => 'Hvem som har laget artikkelen',
        'starts_at' => 'Når arrangementet tilknyttet artikkelen begynner',
      ],
    ],
  ],


  /**
   *
   * /// ---------------------------------------------------------------------------------------------------------
   * /// Automation Emails
   * ///
   * /// Type the ID of the automation-email and
   * /// ---------------------------------------------------------------------------------------------------------
   *
   */
  'mails' => [
    /// Example entry, the id is set to -1 to avoid unintended side effects, this id should be a positive integer
    /// corresponding to an automation mail in netflex.
    -1 => [
      /// Insert names of the includes you want to include
      'include' => ['customer', 'order', 'article'],

      /// Here you can add automation-mail specific fields that can not be shared between automation-mails.
      'attributes' => [
        'membership' => [
          '_group_header' => 'Medlemskap',
          'has_paid' => 'Om brukeren har betalt', /// This resolves to 'data.membership.has_paid'
          'paid_at' => 'Brukeren betalte den'   /// This resolves to 'data.membership.paid_at
        ],
      ],
    ]
  ]
];
