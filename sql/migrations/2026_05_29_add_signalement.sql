-- Signalements : un utilisateur peut signaler un avis ou un commentaire.
-- Table polymorphe (cible_type + cible_id) pour éviter deux tables séparées.
-- Pas de FK sur cible_id : on accepte des signalements orphelins si l'avis/com.
-- est supprimé entretemps (on les nettoie via le statut).

CREATE TABLE IF NOT EXISTS signalement (
  id_signalement   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  cible_type       ENUM('avis','commentaire') NOT NULL,
  cible_id         INT UNSIGNED    NOT NULL,
  motif            VARCHAR(60)     NOT NULL,
  details          VARCHAR(500)    DEFAULT NULL,
  statut           ENUM('en_attente','traite','rejete') NOT NULL DEFAULT 'en_attente',
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur   INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_signalement),

  -- Un utilisateur ne peut signaler le même contenu qu'une fois
  UNIQUE KEY uq_sig_user_cible (id_utilisateur, cible_type, cible_id),

  CONSTRAINT fk_sig_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Filtres habituels côté admin : statut puis date.
CREATE INDEX idx_sig_statut ON signalement (statut, created_at);
