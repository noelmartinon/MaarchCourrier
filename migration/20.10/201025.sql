-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 20.10.24_TMA1 to 20.10.25_TMA1                           --
--                                                                          --
--                                                                          --
-- *************************************************************************--

update groupbasket SET list_event_data = jsonb_set(list_event_data, '{goToNextDocument}', 'true') WHERE list_event='signatureBookAction' and basket_id = 'EsigBasket';
