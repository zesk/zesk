function Tab_Click(name, oldState, newState)
{
	var ow = false, w;
	if (oldState == newState)
		return oldState;
	w = Object_GetStyle('a' + name + '_' + newState);
	var t_backgroundColor = w.backgroundColor;
	if (oldState != false) {
		ow = Object_GetStyle('a' + name + '_' + oldState);
		ObjectID_DisplayHide(oldState);
	}
	if (ow) {
		ow.backgroundColor 		= "#DDE";
		ow.borderBottomColor	= "#778";
	}
	w.backgroundColor 	= "white";
	w.borderBottomColor	= "white";
	ObjectID_DisplayShow(newState);
	return newState;
}
