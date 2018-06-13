# Data Handling with Event Sourcing

This package adjusts the data handling components of TYPO3 CMS to utilize
event sourcing, based on https://github.com/TYPO3Incubator/event_sourcing.

## Warning

This implementation is a proof-of-concept prototype and thus experimental
development. Since not all domain events are implemented, this extension
should not be used for production sites.

## General meaning

### Command

Everything a user/actor has submitted to be executed, not knowing yet
whether it will be possible at all due to permission aspects or availability
of data. Several general commands are translated into more specific commands
(e.g. when editing an existing live record in a workspace - this leads to
an implicit workspace branch command as well as modification commands)

### Entity/GenericEntity

A `GenericEntity` is against the nature of DDD, however the current idea of
dealing with arbitrary database records - which are converted to these kind
of generic entities. They decide on what shall happen with according commands,
accept or deny them and issue according events (in terms of "things that really
happened").

### Event

Actual changes that "really happened". Applying all events for a particular
entity results in the expected state - this is done during the projection
phase. Projections can be used to create materialized views as well (e.g.
a database table that holds the state of all versioned elements per workspace,
page-id and optionally entity name).
