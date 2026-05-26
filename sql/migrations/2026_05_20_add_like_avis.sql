-- Like sur un avis (1 like par utilisateur par avis)
CREATE TABLE IF NOT EXISTS like_avis (
    id_utilisateur  INT UNSIGNED NOT NULL,
    id_avis         INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_utilisateur, id_avis),

    CONSTRAINT fk_like_avis_user
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
        ON DELETE CASCADE,

    CONSTRAINT fk_like_avis_avis
        FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
        ON DELETE CASCADE

) ENGINE=InnoDB;
