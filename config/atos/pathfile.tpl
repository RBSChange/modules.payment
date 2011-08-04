#########################################################################
#
#	Pathfile 
#
#	Liste fichiers parametres utilises par le module de paiement
#
#########################################################################

#-------------------------------------------------------------------------
# Activation (YES) / Desactivation (NO) du mode DEBUG
#-------------------------------------------------------------------------
#
DEBUG!NO!

# ------------------------------------------------------------------------
# Chemin vers le repertoire des logos depuis le web alias  
# Exemple pour le repertoire www.merchant.com/cyberplus/payment/logo/
# indiquer:
# ------------------------------------------------------------------------
#
D_LOGO!/media/frontoffice/!
#
#------------------------------------------------------------------------
#  Fichiers parametres lies a l'api cyberplus paiement	
#------------------------------------------------------------------------
#
# fichier des  parametres mercanet
#
F_DEFAULT!{PROJECT_HOME}/modules/payment/config/atos/parmcom.cyberplus!
#
# fichier parametre commercant
#
F_PARAM!{PROJECT_HOME}/build/atos/parmcom!
# certificat du commercant
#
F_CERTIFICATE!{PROJECT_HOME}/build/atos/certif!
#
# --------------------------------------------------------------------------
# 	end of file
# --------------------------------------------------------------------------