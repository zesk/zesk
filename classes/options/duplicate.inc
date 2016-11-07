<?php

/**
 * This object is used to duplicate large objects which have a lot of inter-references.
 * 
 * While duplicating the object, references to old and new object are managed, and
 * allows for defaults to be applied to the new objects during duplication.
 * 
 * @author kent
 *
 */
class Options_Duplicate extends zesk\Options_Duplicate {}
