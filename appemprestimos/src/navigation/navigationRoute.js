import Splash from '../containers/Splash';
import OnBoarding from '../containers/OnBoarding';
import AuthNavigation from './type/AuthNavigation';
import TabNavigation from './type/TabNavigation';
import SignIn from '../containers/auth/SignIn';
import SignCliente from '../containers/auth/SignCliente';
import TipoUsuario from '../containers/auth/TipoUsuario';
import PassRecovery from '../containers/auth/PassRecovery';
import OtpAuth from '../containers/auth/OtpAuth';
import VerifyIdentity from '../containers/auth/VerifyIdentity';
import CreatePass from '../containers/auth/CreatePass';
import SignUp from '../containers/auth/SignUp';
import CountryRes from '../containers/auth/CountryRes';
import Reasons from '../containers/auth/Reasons';
import CreatePin from '../containers/auth/CreatePin';
import FaceIdentity from '../containers/auth/FaceIdentity';
import ProofRes from '../containers/auth/ProofRes';
import CardOnBoarding from '../containers/auth/CardOnBoarding';
import CardStyle from '../containers/auth/CardStyle';
import NewCard from '../containers/auth/NewCard';
import HomeScreen from '../containers/screen/HomeScreen';
import HomeClienteScreen from '../containers/screen/HomeClienteScreen';
import MyCardScreen from '../containers/screen/MyCardScreen';
import ActivityScreen from '../containers/screen/ActivityScreen';
import ProfileScreen from '../containers/screen/ProfileScreen';
import TransferMoney from '../components/homeTab/TransferMoney';
import SelecionarEmpresa from '../components/homeTab/SelecionarEmpresa';
import Clientes from '../components/homeTab/Clientes';
import DetalhesEmprestimos from '../components/homeTab/DetalhesEmprestimos';
import AcompanharMensalidades from '../components/homeTab/AcompanharMensalidades';
import PixParcela from '../components/homeTab/PixParcela';
import SendMoney from '../components/homeTab/SendMoney';
import TransferProof from '../components/homeTab/TransferProof';
import TopUpScreen from '../components/homeTab/TopUpScreen';
import FechamentoCaixaScreen from '../components/homeTab/FechamentoCaixaScreen';
import ConfiguracoesCaixaScreen from '../components/homeTab/ConfiguracoesCaixaScreen';
import SacarCaixaScreen from '../components/homeTab/SacarCaixaScreen';
import DepositarCaixaScreen from '../components/homeTab/DepositarCaixaScreen';
import Confirmation from '../components/homeTab/Confirmation';
import WithDrawBalance from '../components/homeTab/WithDrawBalance';
import HistoryTrans from '../components/homeTab/HistoryTrans';
import HistoryDetails from '../components/homeTab/HistoryDetails';
import SeeMyCard from '../components/myCardTab/SeeMyCard';
import EditCard from '../components/myCardTab/EditCard';
import AccountInfo from '../components/profileTab/AccountInfo';
import EditAccount from '../components/profileTab/EditAccount';
import SelectLanguage from '../components/profileTab/SelectLanguage';
import GeneralSetting from '../components/profileTab/GeneralSetting';
import ReferralCode from '../components/profileTab/ReferralCode';
import ContactsList from '../components/profileTab/ContactsList';
import Notification from '../components/profileTab/Notification';
import FQA from '../components/profileTab/FQA';
import ActivityGraph from '../components/activityTab/ActivityGraph';
import MoreOptions from '../components/homeTab/MoreOptions';
import ChatScreen from '../containers/moreOpctions/ChatAssistant/ChatScreen';
import ATMDetails from '../containers/moreOpctions/ATMFinder/ATMDetails';
import CobrancaMap from '../containers/moreOpctions/ATMFinder/CobrancaMap';
import BaixaMap from '../containers/moreOpctions/ATMFinder/BaixaMap';
import Aprovacao from '../containers/moreOpctions/ATMFinder/Aprovacao';
import ClientMap from '../containers/moreOpctions/ATMFinder/ClientMap';
import SelectProvider from '../containers/moreOpctions/MobileTopUp/SelectProvider';
import TopUpModal from '../components/modals/TopUpModal';
import PhoneBook from '../containers/moreOpctions/MobileTopUp/PhoneBook';
import LogOut from '../components/profileTab/LogOut';
import AlterarEmpresa from '../components/profileTab/AlterarEmpresa';
import CadastroCliente from '../containers/auth/CadastroCliente';
import AssinaturaContratos from '../containers/assinatura/AssinaturaContratos';
import AssinaturaContratoFlow from '../containers/assinatura/AssinaturaContratoFlow';


export const StackRoute = {
  Splash,
  OnBoarding,
  AuthNavigation,
  TabNavigation,
  TransferMoney,
  Clientes,
  DetalhesEmprestimos,
  AcompanharMensalidades,
  PixParcela,
  SendMoney,
  TransferProof,
  TopUpScreen,
  FechamentoCaixaScreen,
  ConfiguracoesCaixaScreen,
  SacarCaixaScreen,
  DepositarCaixaScreen,
  Confirmation,
  WithDrawBalance,
  HistoryTrans,
  HistoryDetails,
  SeeMyCard,
  EditCard,
  AccountInfo,
  EditAccount,
  SelectLanguage,
  GeneralSetting,
  ReferralCode,
  ContactsList,
  Notification,
  FQA,
  ActivityGraph,
  MoreOptions,
  ChatScreen,
  ATMDetails,
  CobrancaMap,
  BaixaMap,
  Aprovacao,
  ClientMap,
  SelectProvider,
  TopUpModal,
  PhoneBook,
  LogOut,
  AlterarEmpresa,
  CadastroCliente,
  AssinaturaContratos,
  AssinaturaContratoFlow
};

export const AuthRoute = {
  TipoUsuario,
  SignCliente,
  SignIn,
  PassRecovery,
  OtpAuth,
  VerifyIdentity,
  CreatePass,
  SelecionarEmpresa,
  SignUp,
  CountryRes,
  Reasons,
  CreatePin,
  FaceIdentity,
  ProofRes,
  CardOnBoarding,
  CardStyle,
  NewCard,
};

export const TabRoute = {
  HomeScreen,
  HomeClienteScreen,
  MyCardScreen,
  ActivityScreen,
  ProfileScreen,
};
